import os
import re
import sys
import urllib.request

# Import order matters on Windows: torch and paddlepaddle-gpu each bundle
# their own private copy of cuDNN 9. Whichever loads its cuDNN DLLs into the
# process second fails (WinError 127, "procedure not found") because Windows
# resolves same-named DLL dependencies against the already-loaded module.
# Fix in place: this machine's paddle install has its own
# nvidia/cudnn/bin renamed to bin_disabled_use_torch_cudnn, so paddle falls
# back to torch's already-loaded cuDNN instead of loading a conflicting copy
# of its own. That only works if torch loads first — importing it here,
# before any paddle/paddleocr import anywhere in this process, guarantees
# that regardless of what the caller (backend_server.py / detect_plate.py)
# imports first.
import torch  # noqa: F401


def download_model(model_path):
    """Download the license plate detection YOLOv8 weights from Hugging Face if not present."""
    model_dir = os.path.dirname(model_path)
    if not os.path.exists(model_dir):
        os.makedirs(model_dir, exist_ok=True)

    if not os.path.exists(model_path):
        url = "https://huggingface.co/Koushim/yolov8-license-plate-detection/resolve/main/best.pt"
        print(f"[{model_path}] Downloading license plate model weights...", file=sys.stderr)
        try:
            urllib.request.urlretrieve(url, model_path)
            print(f"[{model_path}] Download complete.", file=sys.stderr)
        except Exception as e:
            print(f"[{model_path}] Error downloading model: {e}", file=sys.stderr)
            raise


def sanitize_plate_number(text):
    """Sanitize detected license plate text (keep alphanumeric, space, and hyphen, convert to uppercase)."""
    text = text.upper()

    # Filter out common temporary/slogan/regional words
    blacklist = {
        "REGISTERED", "REGISTRATION", "TEMPORARY", "CONDUCTION", "STICKER",
        "DEALER", "MV", "FILE", "MVFILE", "FILENO", "NO", "FRONT", "REAR",
        "MATATAG", "PILIPINAS", "BAGONG", "NCR", "REGION", "PHILIPPINES"
    }

    words = text.split()
    filtered_words = []
    for word in words:
        # Strip non-alphanumeric to check against blacklist (e.g. "NO." -> "NO")
        cleaned_word = re.sub(r'[^A-Z0-9]', '', word)
        if cleaned_word not in blacklist:
            filtered_words.append(word)

    text = " ".join(filtered_words)

    cleaned = re.sub(r'[^a-zA-Z0-9\s-]', '', text)
    cleaned = re.sub(r'[\s-]+', ' ', cleaned)
    return cleaned.strip().upper()


def score_text(text):
    """Score a text candidate based on how well it fits a license plate format."""
    s = re.sub(r'[^A-Z0-9]', '', text.upper())
    if not s:
        return 0.0

    has_letters = any(c.isalpha() for c in s)
    has_numbers = any(c.isdigit() for c in s)

    score = 1.0
    length = len(s)

    if 5 <= length <= 8:
        score += 2.0
    elif length == 3 or length == 4:
        score += 1.0
    else:
        score -= 2.0

    if has_letters and has_numbers:
        score += 3.0
    elif has_letters:
        score += 0.5
    elif has_numbers:
        score += 0.5

    return score


def get_merged_lines(rec_texts, rec_scores, rec_boxes, img_height):
    """Group PaddleOCR text blocks horizontally and merge them left-to-right.

    rec_texts/rec_scores/rec_boxes are the parallel arrays PaddleOCR 3.x
    returns per detected line (paddlex OCRResult: result['rec_texts'],
    result['rec_scores'], result['rec_boxes']). rec_boxes rows are
    [x1, y1, x2, y2].

    Text blocks under 45% of the tallest block's height are dropped first
    (filters small background noise/region labels like "NCR" that would
    otherwise pollute the merged line or its length-based score).
    """
    if len(rec_texts) == 0:
        return []

    parsed_items = []
    max_height = 0.0

    for text, conf, box in zip(rec_texts, rec_scores, rec_boxes):
        x1, y1, x2, y2 = box
        x_min = float(x1)
        y_center = (float(y1) + float(y2)) / 2
        height = float(y2) - float(y1)

        if height > max_height:
            max_height = height
        parsed_items.append({
            'text': text,
            'x_min': x_min,
            'y_center': y_center,
            'height': height,
            'conf': conf,
        })

    height_threshold = max_height * 0.45
    filtered_items = [item for item in parsed_items if item['height'] >= height_threshold]

    if not filtered_items:
        return []

    lines = []
    y_threshold = max(40, img_height * 0.20)

    for item in filtered_items:
        added = False
        for group in lines:
            if abs(group['y_center'] - item['y_center']) < y_threshold:
                group['items'].append(item)
                group['y_center'] = (group['y_center'] * (len(group['items']) - 1) + item['y_center']) / len(group['items'])
                added = True
                break
        if not added:
            lines.append({
                'y_center': item['y_center'],
                'items': [item],
            })

    merged_texts = []
    for group in lines:
        sorted_items = sorted(group['items'], key=lambda x: x['x_min'])
        merged_text = " ".join([item['text'] for item in sorted_items])
        avg_conf = sum([item['conf'] for item in sorted_items]) / len(sorted_items)
        merged_texts.append((merged_text, avg_conf))

    return merged_texts


def run_paddle_ocr(paddle_ocr, image_path, telemetry):
    """Run PaddleOCR on an image path, return (sanitized_text, confidence)."""
    import cv2

    img = cv2.imread(image_path)
    if img is None:
        return "", 0.0
    img_height = img.shape[0]

    with telemetry.phase("paddle"):
        try:
            result = paddle_ocr.predict(image_path)
        except Exception:
            import traceback
            traceback.print_exc(file=sys.stderr)
            result = None

    text = ""
    confidence = 0.0
    if result and len(result) > 0 and result[0] is not None:
        r = result[0]
        merged_lines = get_merged_lines(r['rec_texts'], r['rec_scores'], r['rec_boxes'], img_height)
        if merged_lines:
            best_text, best_conf, best_score = "", 0.0, -999.0
            for line_text, line_conf in merged_lines:
                sanitized_line = sanitize_plate_number(line_text)
                scr = score_text(sanitized_line)
                if scr > best_score:
                    best_score, best_text, best_conf = scr, sanitized_line, line_conf
            if best_text:
                text, confidence = best_text, best_conf

    return sanitize_plate_number(text), confidence


def detect_and_recognize(yolo_model, paddle_ocr, image_path, base_dir, telemetry, device):
    """Run the full YOLOv8 detect -> crop -> PaddleOCR pipeline on one image.

    Mirrors the original detect_plate.py main() logic, minus EasyOCR.
    """
    import cv2

    img = cv2.imread(image_path)
    if img is None:
        return {"success": False, "error": "Failed to read image with OpenCV"}

    h, w, _ = img.shape

    with telemetry.phase("yolo"):
        # conf=0.05 (down from ultralytics default 0.25): close-up vehicle
        # captures dominate the frame and score lower than the training
        # distribution expects, so the default threshold was missing them.
        results = yolo_model(img, device=device, verbose=False, conf=0.05)[0]

    best_box = None
    best_conf = 0.0
    for box in results.boxes:
        conf = float(box.conf[0])
        if conf > best_conf:
            best_conf = conf
            best_box = box

    base_name, ext = os.path.splitext(image_path)
    cropped_path = f"{base_name}_cropped{ext}"

    if best_box is not None:
        x1, y1, x2, y2 = map(int, best_box.xyxy[0])
        # 18% width / 10% height padding (up from 8%/8%): prevents edge
        # characters (e.g. the "N" in "NDP 9668") from being cropped off.
        pad_w = int((x2 - x1) * 0.18)
        pad_h = int((y2 - y1) * 0.10)
        x1_crop = max(0, x1 - pad_w)
        y1_crop = max(0, y1 - pad_h)
        x2_crop = min(w, x2 + pad_w)
        y2_crop = min(h, y2 + pad_h)

        cropped_img = img[y1_crop:y2_crop, x1_crop:x2_crop]
        cv2.imwrite(cropped_path, cropped_img)

        plate_text, ocr_conf = run_paddle_ocr(paddle_ocr, cropped_path, telemetry)
        score_crop = score_text(plate_text)
        if score_crop < 5.0:
            full_text, full_conf = run_paddle_ocr(paddle_ocr, image_path, telemetry)
            score_full = score_text(full_text)
            if score_full > score_crop and score_full >= 4.0:
                plate_text, ocr_conf = full_text, full_conf

        total_confidence = round((best_conf + ocr_conf) / 2 * 100, 2) if ocr_conf > 0 else round(best_conf * 100, 2)

        if not plate_text:
            plate_text = "UNKNOWN"
            total_confidence = round(best_conf * 100, 2)

        relative_cropped_path = os.path.relpath(cropped_path, base_dir).replace('\\', '/')

        result = {
            "success": True,
            "plate_text": plate_text,
            "confidence": total_confidence,
            "cropped_path": relative_cropped_path,
            "detection_confidence": round(best_conf * 100, 2),
            "ocr_confidence": round(ocr_conf * 100, 2),
            "ocr_engine": "paddleocr" if plate_text != "UNKNOWN" else "none",
        }
    else:
        crop_h, crop_w = int(h * 0.3), int(w * 0.5)
        y1_crop, x1_crop = int(h * 0.4), int(w * 0.25)
        cropped_img = img[y1_crop:y1_crop + crop_h, x1_crop:x1_crop + crop_w]
        cv2.imwrite(cropped_path, cropped_img)

        plate_text, ocr_conf = run_paddle_ocr(paddle_ocr, image_path, telemetry)
        score_full = score_text(plate_text)

        if score_full < 4.0:
            crop_text, crop_conf = run_paddle_ocr(paddle_ocr, cropped_path, telemetry)
            score_crop = score_text(crop_text)
            if score_crop > score_full:
                plate_text, ocr_conf = crop_text, crop_conf

        relative_cropped_path = os.path.relpath(cropped_path, base_dir).replace('\\', '/')

        if plate_text and len(plate_text) >= 4:
            result = {
                "success": True,
                "plate_text": plate_text,
                "confidence": round(ocr_conf * 100, 2),
                "cropped_path": relative_cropped_path,
                "detection_confidence": 0.0,
                "ocr_confidence": round(ocr_conf * 100, 2),
                "ocr_engine": "paddleocr",
            }
        else:
            result = {
                "success": False,
                "error": "No license plate detected",
                "fallback_cropped_path": relative_cropped_path,
            }

    result["telemetry"] = telemetry.summary()
    return result
