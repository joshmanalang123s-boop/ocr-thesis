import os
import re
import sys
import urllib.request


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


def run_fast_plate_ocr(ocr_recognizer, image_path, telemetry):
    """Run fast-plate-ocr on an image path, return (sanitized_text, confidence).

    Unlike PaddleOCR, fast-plate-ocr's CCT models classify the whole cropped
    image directly into a single fixed-length plate string in one pass -
    no separate text-detection step, so there's no multi-line output to
    merge or filter (it copes with surrounding noise like temp-plate stamps
    on its own; verified against real crops during the engine spike).
    """
    with telemetry.phase("ocr"):
        try:
            results = ocr_recognizer.run(image_path, return_confidence=True)
        except Exception:
            import traceback
            traceback.print_exc(file=sys.stderr)
            results = []

    text = ""
    confidence = 0.0
    if results and results[0].plate:
        pred = results[0]
        text = pred.plate
        if pred.char_probs is not None and len(pred.char_probs) > 0:
            confidence = float(sum(pred.char_probs) / len(pred.char_probs))

    return sanitize_plate_number(text), confidence


def detect_and_recognize(yolo_model, ocr_recognizer, image_path, base_dir, telemetry, device):
    """Run the full YOLOv8 detect -> crop -> fast-plate-ocr pipeline on one image.

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

        plate_text, ocr_conf = run_fast_plate_ocr(ocr_recognizer, cropped_path, telemetry)
        score_crop = score_text(plate_text)
        if score_crop < 5.0:
            full_text, full_conf = run_fast_plate_ocr(ocr_recognizer, image_path, telemetry)
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
            "ocr_engine": "fastplateocr" if plate_text != "UNKNOWN" else "none",
        }
    else:
        crop_h, crop_w = int(h * 0.3), int(w * 0.5)
        y1_crop, x1_crop = int(h * 0.4), int(w * 0.25)
        cropped_img = img[y1_crop:y1_crop + crop_h, x1_crop:x1_crop + crop_w]
        cv2.imwrite(cropped_path, cropped_img)

        plate_text, ocr_conf = run_fast_plate_ocr(ocr_recognizer, image_path, telemetry)
        score_full = score_text(plate_text)

        if score_full < 4.0:
            crop_text, crop_conf = run_fast_plate_ocr(ocr_recognizer, cropped_path, telemetry)
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
                "ocr_engine": "fastplateocr",
            }
        else:
            result = {
                "success": False,
                "error": "No license plate detected",
                "fallback_cropped_path": relative_cropped_path,
            }

    result["telemetry"] = telemetry.summary()
    return result
