import os
import re
import sys
import urllib.request
import cv2
import numpy as np


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


# Slogan blacklist & patterns for Philippine license plates
SLOGAN_BLACKLIST = {
    "REGISTERED", "REGISTRATION", "TEMPORARY", "CONDUCTION", "STICKER",
    "DEALER", "MV", "FILE", "MVFILE", "FILENO", "NO", "FRONT", "REAR",
    "MATATAG", "PILIPINAS", "BAGONG", "NCR", "REGION", "PHILIPPINES",
    "REPUBLIKA", "REPUBLIC", "BAYAN", "RIZAL", "ANG", "LTO", "PEOPLE", "POWER",
    "NA", "NG", "SA"
}

SLOGAN_PATTERNS = [
    r'MATATAG', r'REPUBLIK', r'PUBLIK', r'PILIPIN', r'BAGONG', r'BAYAN',
    r'PEOPLE', r'POWER', r'PHILIPP'
]


def is_slogan_text(text):
    """Detect if a text string is a slogan or slogan fragment from Philippine plates."""
    s = re.sub(r'[^A-Z0-9]', '', text.upper())
    if not s or len(s) < 3:
        return False
    for pat in SLOGAN_PATTERNS:
        if re.search(pat, s):
            return True
    # Fuzzy match common OCR distortions on "REPUBLIKA"
    replacements = {'0': 'O', '8': 'B', '1': 'I', '2': 'R', '5': 'S'}
    norm = "".join(replacements.get(c, c) for c in s)
    if any(k in norm for k in ["PUBLIK", "EPUBLIK", "PEBLIK", "PU8LIK", "UBLIKA", "PUBEIK", "0ULLLKA"]):
        return True
    return False


def is_green_image(img):
    """Detect if an image or crop has characteristic green plate colors."""
    if img is None or img.size == 0:
        return False
    hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    green_mask = cv2.inRange(hsv, (35, 25, 25), (85, 255, 255))
    ratio = np.count_nonzero(green_mask) / float(img.shape[0] * img.shape[1])
    return ratio > 0.035


def fix_green_plate_text(text, is_green=False, has_slogan=False, src=""):
    """
    Format and correct Philippine license plate text according to LTO standards:
    - 7-character vehicle plates: 3 letters + 4 digits (e.g. 'NBC 1234', 'NEY 1228')
    - 6-character vehicle plates: 3 letters + 3 digits (e.g. 'NBC 123', 'YEC 652')
    - 5-character vehicle plates: 3 letters + 2 digits (e.g. 'EOJ 69')
    - Motorcycle plates: 3 digits + 3 letters (e.g. '123 MBC') or 2 letters + 4-5 digits
    - Pre-2014 green plate Rizal monument obelisk artifacts corrected when slogan/green plate confirmed
    """
    s = re.sub(r'[^A-Z0-9]', '', text.upper())
    if not s:
        return ""

    # Rizal monument obelisk correction: ONLY if an actual slogan was found in the frame
    # OR the candidate is a tight plate crop (not full/center frame) that is specifically green.
    # Pre-2014 green plates ALWAYS have 3 digits (or 2 digits), NEVER 4 digits.
    if has_slogan or (is_green and src not in ("full_inner", "center_crop") and len(s) == 7):
        m_obelisk = re.match(r'^([A-Z]{3})([14IT7])([0-9]{3})$', s)
        if m_obelisk:
            return f"{m_obelisk.group(1)} {m_obelisk.group(3)}"

    # 1. Canonical 7-character vehicle plate: 3 letters + 4 digits (e.g. NBC 1234, NEY 1228)
    m = re.match(r'^([A-Z]{3})([0-9]{4})$', s)
    if m:
        return f"{m.group(1)} {m.group(2)}"

    # 2. Canonical 6-character vehicle plate: 3 letters + 3 digits (e.g. NBC 123, YEC 652)
    m = re.match(r'^([A-Z]{3})([0-9]{3})$', s)
    if m:
        return f"{m.group(1)} {m.group(2)}"

    # 3. Canonical 5-character vehicle plate: 3 letters + 2 digits (e.g. EOJ 69)
    m = re.match(r'^([A-Z]{3})([0-9]{2})$', s)
    if m:
        return f"{m.group(1)} {m.group(2)}"

    # 4. Canonical motorcycle plate: 3 digits + 3 letters (e.g. 123 MBC)
    m = re.match(r'^([0-9]{3})([A-Z]{3})$', s)
    if m:
        return f"{m.group(1)} {m.group(2)}"

    # 5. Canonical modern motorcycle plate: 2 letters + 4 or 5 digits (e.g. MC 12345)
    m = re.match(r'^([A-Z]{2})([0-9]{4,5})$', s)
    if m:
        return f"{m.group(1)} {m.group(2)}"

    # Generic separation fallback (letters then digits, or digits then letters)
    m = re.match(r'^([A-Z]+)([0-9]+)$', s)
    if m:
        return f"{m.group(1)} {m.group(2)}"

    m = re.match(r'^([0-9]+)([A-Z]+)$', s)
    if m:
        return f"{m.group(1)} {m.group(2)}"

    return text


def sanitize_plate_number(text, is_green=False, has_slogan=False, src=""):
    """Sanitize detected license plate text (keep alphanumeric, space, and hyphen, convert to uppercase)."""
    text = text.upper()
    words = text.split()
    filtered_words = []
    for word in words:
        cleaned_word = re.sub(r'[^A-Z0-9]', '', word)
        if cleaned_word not in SLOGAN_BLACKLIST and not is_slogan_text(cleaned_word):
            filtered_words.append(word)

    text = " ".join(filtered_words)
    cleaned = re.sub(r'[^a-zA-Z0-9\s-]', '', text)
    cleaned = re.sub(r'[\s-]+', ' ', cleaned).strip().upper()

    return fix_green_plate_text(cleaned, is_green=is_green, has_slogan=has_slogan, src=src)


def score_text(text):
    """
    Score the plate string structure:
    Higher score for exact canonical Philippine formats.
    """
    s = re.sub(r'[^A-Z0-9]', '', text.upper())
    if not s or is_slogan_text(s):
        return 0.0

    # 7-char standard: LLL DDDD (e.g. NBC 1234, NEY 1228) -> highest priority for modern vehicles
    if re.match(r'^[A-Z]{3}[0-9]{4}$', s):
        return 8.0

    # 6-char standard: LLL DDD (e.g. NBC 123, YEC 652) -> high priority
    if re.match(r'^[A-Z]{3}[0-9]{3}$', s):
        return 7.5

    # Motorcycle DDD LLL: (e.g. 123 MBC)
    if re.match(r'^[0-9]{3}[A-Z]{3}$', s):
        return 7.5

    # 5-char: LLL DD (e.g. EOJ 69)
    if re.match(r'^[A-Z]{3}[0-9]{2}$', s):
        return 7.0

    # Modern motorcycle LL DDDDD
    if re.match(r'^[A-Z]{2}[0-9]{4,5}$', s):
        return 7.0

    # Mixed letters and digits
    has_letters = any(c.isalpha() for c in s)
    has_digits = any(c.isdigit() for c in s)
    if has_letters and has_digits:
        if 5 <= len(s) <= 8:
            return 5.0
        return 3.0

    return 1.0


def detect_green_plate_rects(img):
    """Detect candidate bounding boxes for green license plates via morphology and contours."""
    h, w, _ = img.shape
    candidates = []

    # Green mask morphology
    hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    lower_green = np.array([35, 20, 20])
    upper_green = np.array([90, 255, 255])
    green_mask = cv2.inRange(hsv, lower_green, upper_green)

    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (35, 20))
    closed = cv2.morphologyEx(green_mask, cv2.MORPH_CLOSE, kernel)

    contours, _ = cv2.findContours(closed, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    for c in contours:
        x, y, cw, ch = cv2.boundingRect(c)
        area = cw * ch
        aspect = cw / float(ch) if ch > 0 else 0
        if 1.3 <= aspect <= 5.0 and area > (w * h * 0.04):
            pad_top = int(ch * 0.6)
            pad_bot = int(ch * 0.1)
            pad_sides = int(cw * 0.1)
            x1 = max(0, x - pad_sides)
            y1 = max(0, y - pad_top)
            x2 = min(w, x + cw + pad_sides)
            y2 = min(h, y + ch + pad_bot)
            candidates.append((x1, y1, x2, y2))

    # Rectangular plate outline via Canny
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(blurred, 40, 140)
    dilated = cv2.dilate(edges, cv2.getStructuringElement(cv2.MORPH_RECT, (3, 3)))
    edge_contours, _ = cv2.findContours(dilated, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    for c in edge_contours:
        x, y, cw, ch = cv2.boundingRect(c)
        area = cw * ch
        aspect = cw / float(ch) if ch > 0 else 0
        if 1.5 <= aspect <= 3.8 and (w * h * 0.10) < area < (w * h * 0.90):
            region = img[y:y+ch, x:x+cw]
            if is_green_image(region):
                candidates.append((x, y, x + cw, y + ch))

    return candidates


def run_fast_plate_ocr(ocr_recognizer, image_or_path, telemetry=None, is_green=False, has_slogan=False, src=""):
    """Run fast-plate-ocr on an image path or numpy image, return (sanitized_text, confidence, raw_text)."""
    try:
        if telemetry:
            with telemetry.phase("ocr"):
                results = ocr_recognizer.run(image_or_path, return_confidence=True)
        else:
            results = ocr_recognizer.run(image_or_path, return_confidence=True)
    except Exception:
        import traceback
        traceback.print_exc(file=sys.stderr)
        results = []

    raw_text = ""
    confidence = 0.0
    if results and results[0].plate:
        pred = results[0]
        raw_text = pred.plate
        if pred.char_probs is not None and len(pred.char_probs) > 0:
            confidence = float(sum(pred.char_probs) / len(pred.char_probs))

    sanitized = sanitize_plate_number(raw_text, is_green=is_green, has_slogan=has_slogan, src=src)
    return sanitized, confidence, raw_text


def detect_and_recognize(yolo_model, ocr_recognizer, image_path, base_dir, telemetry, device):
    """Run the enhanced YOLOv8 detect -> crop -> fast-plate-ocr pipeline on one image.

    Features intelligent handling for:
    - Standard 7-character Philippine plates (3 letters + 4 digits, e.g. NBC 1234, NEY 1228)
    - Standard 6-character Philippine plates (3 letters + 3 digits, e.g. NBC 123, YEC 652)
    - 5-character / vanity plates (e.g. EOJ 69) and motorcycle plates (e.g. 123 MBC)
    - Substring / clipped crop resolution (chooses complete plates over clipped fragments)
    - Consensus bonus across multi-crop predictions
    - Slogan detection and upward bounding box expansion
    - Rizal Monument obelisk artifact correction
    - Mirrored webcam fallback
    """
    img = cv2.imread(image_path)
    if img is None:
        return {"success": False, "error": "Failed to read image with OpenCV"}

    h, w, _ = img.shape
    base_name, ext = os.path.splitext(image_path)
    cropped_path = f"{base_name}_cropped{ext}"

    with telemetry.phase("yolo"):
        results = yolo_model(img, device=device, verbose=False, conf=0.10, imgsz=480)[0]

    valid_boxes = [b for b in results.boxes if float(b.conf[0]) >= 0.10]
    if len(valid_boxes) == 0:
        return {
            "success": False,
            "error": "No license plate detected",
            "telemetry": telemetry.summary()
        }

    # Sort boxes by confidence and only evaluate top 3 candidates
    valid_boxes.sort(key=lambda b: float(b.conf[0]), reverse=True)
    eval_boxes = valid_boxes[:3]

    has_slogan = False
    evaluated = []
    text_counts = {}

    # 1. Process top YOLO detections with direct padded crop OCR & fast short-circuiting
    for i, box in enumerate(eval_boxes):
        det_conf = float(box.conf[0])
        bx1, by1, bx2, by2 = map(int, box.xyxy[0])
        bw, bh = bx2 - bx1, by2 - by1

        pad_w = int(bw * 0.10)
        pad_h = int(bh * 0.10)
        cx1 = max(0, bx1 - pad_w)
        cy1 = max(0, by1 - pad_h)
        cx2 = min(w, bx2 + pad_w)
        cy2 = min(h, by2 + pad_h)

        crop = img[cy1:cy2, cx1:cx2]
        if crop.size == 0:
            continue

        clean_txt, ocr_conf, raw_txt = run_fast_plate_ocr(
            ocr_recognizer, crop, telemetry=telemetry, is_green=False, has_slogan=False, src=f"yolo_{i}"
        )

        # Bottom slogan strip detection (e.g. MATATAG, REPUBLIKA, PILIPINAS)
        if is_slogan_text(raw_txt) or is_slogan_text(clean_txt):
            has_slogan = True
            py1 = max(0, int(by1 - bh * 3.6))
            py2 = min(h, int(by2 + bh * 0.4))
            px1 = max(0, int(bx1 - bw * 1.0))
            px2 = min(w, int(bx2 + bw * 2.2))
            exp_crop = img[py1:py2, px1:px2]
            if exp_crop.size > 0:
                clean_txt, ocr_conf, raw_txt = run_fast_plate_ocr(
                    ocr_recognizer, exp_crop, telemetry=telemetry, is_green=True, has_slogan=True, src="slogan_expanded"
                )
                crop = exp_crop

        if not clean_txt or is_slogan_text(raw_txt) or is_slogan_text(clean_txt):
            continue

        fmt_score = score_text(clean_txt)

        # FAST SHORT-CIRCUIT: If canonical Philippine format with good OCR confidence, return immediately!
        if fmt_score >= 7.0 and ocr_conf >= 0.75:
            cv2.imwrite(cropped_path, crop)
            relative_cropped_path = os.path.relpath(cropped_path, base_dir).replace('\\', '/')
            total_confidence = round(ocr_conf * 100, 2)
            if det_conf > 0:
                total_confidence = round(((det_conf + ocr_conf) / 2) * 100, 2)
            return {
                "success": True,
                "plate_text": clean_txt,
                "confidence": total_confidence,
                "cropped_path": relative_cropped_path,
                "detection_confidence": round(det_conf * 100, 2),
                "ocr_confidence": round(ocr_conf * 100, 2),
                "ocr_engine": "fastplateocr",
                "telemetry": telemetry.summary()
            }

        quality = fmt_score * 3.0 + (ocr_conf * 5.0)
        evaluated.append({
            "text": clean_txt,
            "raw": raw_txt,
            "clean_nosps": re.sub(r'[^A-Z0-9]', '', clean_txt),
            "ocr_conf": ocr_conf,
            "det_conf": det_conf,
            "fmt_score": fmt_score,
            "quality": quality,
            "crop": crop,
            "src": f"yolo_{i}",
        })
        text_counts[clean_txt] = text_counts.get(clean_txt, 0) + 1

    # Consensus bonus: reward plate readings found across multiple distinct crops
    for cand in evaluated:
        if text_counts[cand["text"]] > 1:
            cand["quality"] += 2.0 * (text_counts[cand["text"]] - 1)

    # Substring / sub-segment resolution
    for cand in evaluated:
        for other in evaluated:
            if cand != other and len(cand["clean_nosps"]) < len(other["clean_nosps"]):
                if cand["clean_nosps"] in other["clean_nosps"]:
                    if other["ocr_conf"] >= 0.80 and other["fmt_score"] >= 7.0:
                        cand["quality"] -= 6.0

    evaluated.sort(key=lambda x: x["quality"], reverse=True)

    best_candidate = evaluated[0] if evaluated else None
    best_text = best_candidate["text"] if best_candidate else ""
    best_conf = best_candidate["ocr_conf"] if best_candidate else 0.0
    best_det_conf = best_candidate["det_conf"] if best_candidate else 0.0
    best_crop_img = best_candidate["crop"] if best_candidate else None

    # Fallback: check horizontal flip only on best cropped candidate (never full image)
    if best_crop_img is not None and (best_candidate["quality"] < 15.0 or best_conf < 0.40):
        flipped = cv2.flip(best_crop_img, 1)
        f_clean, f_conf, f_raw = run_fast_plate_ocr(
            ocr_recognizer, flipped, telemetry=telemetry, is_green=is_green_image(flipped), has_slogan=has_slogan
        )

        if f_clean and not is_slogan_text(f_raw) and not is_slogan_text(f_clean):
            f_score = score_text(f_clean)
            has_letters = any(c.isalpha() for c in f_clean)
            has_digits = any(c.isdigit() for c in f_clean)
            if has_letters and has_digits and (f_score > best_candidate["fmt_score"] or f_conf > best_conf):
                best_text = f_clean
                best_conf = f_conf
                best_crop_img = flipped

    # Only consider a detection successful if:
    # 1. An actual plate crop exists
    # 2. Text matches a canonical Philippine plate format (score >= 7.0)
    # 3. OCR confidence is at least 60%
    is_valid_format = score_text(best_text) >= 7.0 if best_text else False

    if best_crop_img is not None and best_text and is_valid_format and best_conf >= 0.75:
        cv2.imwrite(cropped_path, best_crop_img)
        relative_cropped_path = os.path.relpath(cropped_path, base_dir).replace('\\', '/')
        total_confidence = round(best_conf * 100, 2)
        if best_det_conf > 0:
            total_confidence = round(((best_det_conf + best_conf) / 2) * 100, 2)

        result = {
            "success": True,
            "plate_text": best_text,
            "confidence": total_confidence,
            "cropped_path": relative_cropped_path,
            "detection_confidence": round(best_det_conf * 100, 2),
            "ocr_confidence": round(best_conf * 100, 2),
            "ocr_engine": "fastplateocr",
        }
    else:
        result = {
            "success": False,
            "error": "No license plate detected",
        }

    result["telemetry"] = telemetry.summary()
    return result
