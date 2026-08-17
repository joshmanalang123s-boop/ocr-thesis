import sys
import json
import os

# Disable mkldnn to avoid PIR executor crash on CPU in PaddleOCR
os.environ["FLAGS_use_mkldnn"] = "0"
os.environ["PADDLE_PDX_ENABLE_MKLDNN_BYDEFAULT"] = "0"

import cv2
import urllib.request
import re
import io

# Force UTF-8 stdout
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

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
    # Convert to uppercase
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
        # Strip non-alphanumeric to check against blacklist (e.g. "NO." -> "NO", "REGISTERED," -> "REGISTERED")
        cleaned_word = re.sub(r'[^A-Z0-9]', '', word)
        if cleaned_word not in blacklist:
            filtered_words.append(word)
            
    text = " ".join(filtered_words)
    
    # Remove extra spaces and keep alphanumeric characters, spaces, and dashes
    cleaned = re.sub(r'[^a-zA-Z0-9\s-]', '', text)
    # Condense multiple spaces/dashes
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
    
    # Length penalty (standard plates are 5-8 chars)
    if 5 <= length <= 8:
        score += 2.0
    elif length == 3 or length == 4:
        score += 1.0
    else:
        score -= 2.0
        
    # Letters & Numbers mix bonus
    if has_letters and has_numbers:
        score += 3.0
    elif has_letters:
        score += 0.5
    elif has_numbers:
        score += 0.5
        
    return score

def get_merged_lines(ocr_result, img_height, is_paddle=False):
    """Group OCR text blocks horizontally and merge them left-to-right."""
    if not ocr_result:
        return []

    # First pass: Parse and calculate heights to filter out small noise (slogans, regions, borders)
    parsed_items = []
    max_height = 0.0
    
    if is_paddle:
        # PaddleOCR format: [ [ [ [x1,y1],[x2,y2],[x3,y3],[x4,y4] ], ('text', conf) ], ... ]
        for line in ocr_result:
            if not line or len(line) < 2:
                continue
            bbox = line[0]
            val = line[1][0]
            conf = line[1][1]
            
            xs = [pt[0] for pt in bbox]
            ys = [pt[1] for pt in bbox]
            x_min = min(xs)
            y_min, y_max = min(ys), max(ys)
            y_center = (y_min + y_max) / 2
            height = y_max - y_min
            
            if height > max_height:
                max_height = height
            parsed_items.append({
                'text': val,
                'x_min': x_min,
                'y_center': y_center,
                'height': height,
                'conf': conf
            })
    else:
        # EasyOCR format: [ (bbox, val, conf), ... ]
        for bbox, val, conf in ocr_result:
            xs = [pt[0] for pt in bbox]
            ys = [pt[1] for pt in bbox]
            x_min = min(xs)
            y_min, y_max = min(ys), max(ys)
            y_center = (y_min + y_max) / 2
            height = y_max - y_min
            
            if height > max_height:
                max_height = height
            parsed_items.append({
                'text': val,
                'x_min': x_min,
                'y_center': y_center,
                'height': height,
                'conf': conf
            })

    # Filter out text blocks that are too small compared to the main characters (e.g. < 45% of max height)
    height_threshold = max_height * 0.45
    filtered_items = [item for item in parsed_items if item['height'] >= height_threshold]
    
    if not filtered_items:
        return []

    # Group into lines horizontally
    lines = []
    y_threshold = max(40, img_height * 0.20)
    
    for item in filtered_items:
        added = False
        for group in lines:
            if abs(group['y_center'] - item['y_center']) < y_threshold:
                group['items'].append(item)
                group['y_center'] = (group['y_center'] * (len(group['items'])-1) + item['y_center']) / len(group['items'])
                added = True
                break
        if not added:
            lines.append({
                'y_center': item['y_center'],
                'items': [item]
            })

    # Sort items in each line by x_min (left-to-right) and merge
    merged_texts = []
    for group in lines:
        sorted_items = sorted(group['items'], key=lambda x: x['x_min'])
        merged_text = " ".join([item['text'] for item in sorted_items])
        avg_conf = sum([item['conf'] for item in sorted_items]) / len(sorted_items)
        merged_texts.append((merged_text, avg_conf))
        
    return merged_texts

def run_ocr(image_path):
    """Run EasyOCR on the cropped plate image, with PaddleOCR as a fallback."""
    text = ""
    confidence = 0.0
    engine_used = "none"

    # Get image dimensions to use in thresholding
    img = cv2.imread(image_path)
    if img is None:
        return "", 0.0, "none"
    img_height = img.shape[0]

    # 1. Try EasyOCR first (it is stable on Windows CPU and doesn't crash)
    try:
        import easyocr
        # Initialize EasyOCR reader (downloads models on first run)
        reader = easyocr.Reader(['en'], gpu=False)
        
        # Load and convert image to grayscale (2D array) to avoid shape unpacking bug in EasyOCR
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        ocr_result = reader.readtext(gray)
        
        if ocr_result:
            merged_lines = get_merged_lines(ocr_result, img_height, is_paddle=False)
            if merged_lines:
                # Find the highest scoring line
                best_line_text = ""
                best_line_conf = 0.0
                best_line_score = -999.0
                for line_text, line_conf in merged_lines:
                    sanitized_line = sanitize_plate_number(line_text)
                    scr = score_text(sanitized_line)
                    if scr > best_line_score:
                        best_line_score = scr
                        best_line_text = sanitized_line
                        best_line_conf = line_conf
                if best_line_text:
                    text = best_line_text
                    confidence = best_line_conf
                    engine_used = "easyocr"
    except Exception as e:
        import traceback
        traceback.print_exc(file=sys.stderr)
        print(f"EasyOCR failed/not available: {e}. Falling back to PaddleOCR...", file=sys.stderr)

    # 2. Try PaddleOCR as a fallback
    if not text:
        try:
            import logging
            logging.getLogger("ppocr").setLevel(logging.ERROR)
            from paddleocr import PaddleOCR
            # Initialize PaddleOCR (downloads models on first run)
            ocr = PaddleOCR(use_angle_cls=False, lang='en')
            ocr_result = ocr.ocr(image_path)
            
            if ocr_result and len(ocr_result) > 0 and ocr_result[0] is not None:
                merged_lines = get_merged_lines(ocr_result[0], img_height, is_paddle=True)
                if merged_lines:
                    # Find the highest scoring line
                    best_line_text = ""
                    best_line_conf = 0.0
                    best_line_score = -999.0
                    for line_text, line_conf in merged_lines:
                        sanitized_line = sanitize_plate_number(line_text)
                        scr = score_text(sanitized_line)
                        if scr > best_line_score:
                            best_line_score = scr
                            best_line_text = sanitized_line
                            best_line_conf = line_conf
                    if best_line_text:
                        text = best_line_text
                        confidence = best_line_conf
                        engine_used = "paddleocr"
        except Exception as e:
            print(f"PaddleOCR failed/not available: {e}.", file=sys.stderr)

    return sanitize_plate_number(text), confidence, engine_used

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Missing input image path argument"}))
        sys.exit(1)

    image_path = sys.argv[1]
    if not os.path.exists(image_path):
        print(json.dumps({"success": False, "error": f"Image file not found: {image_path}"}))
        sys.exit(1)

    # Resolve paths
    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    model_path = os.path.join(base_dir, "storage", "app", "models", "yolov8n_plate.pt")

    try:
        # Step 1: Ensure YOLOv8 model is downloaded
        download_model(model_path)

        # Step 2: Load YOLOv8 model
        from ultralytics import YOLO
        model = YOLO(model_path)

        # Step 3: Run YOLOv8 detection
        img = cv2.imread(image_path)
        if img is None:
            print(json.dumps({"success": False, "error": "Failed to read image with OpenCV"}))
            sys.exit(1)
            
        h, w, _ = img.shape
        # Run YOLOv8 detection with conf=0.05 to capture close-up plates
        results = model(img, verbose=False, conf=0.05)[0]

        # Step 4: Process detections
        best_box = None
        best_conf = 0.0

        for box in results.boxes:
            conf = float(box.conf[0])
            if conf > best_conf:
                best_conf = conf
                best_box = box

        base_name, ext = os.path.splitext(image_path)
        cropped_path = f"{base_name}_cropped{ext}"

        # If a license plate is found
        if best_box is not None:
            x1, y1, x2, y2 = map(int, best_box.xyxy[0])
            
            # Pad the bounding box (18% width, 10% height) to prevent characters from being cut off
            pad_w = int((x2 - x1) * 0.18)
            pad_h = int((y2 - y1) * 0.10)
            
            x1_crop = max(0, x1 - pad_w)
            y1_crop = max(0, y1 - pad_h)
            x2_crop = min(w, x2 + pad_w)
            y2_crop = min(h, y2 + pad_h)

            cropped_img = img[y1_crop:y2_crop, x1_crop:x2_crop]
            cv2.imwrite(cropped_path, cropped_img)
            
            # Run OCR on the cropped plate
            plate_text, ocr_conf, ocr_engine = run_ocr(cropped_path)
            
            # If the crop OCR is already a strong license plate match, we skip full image OCR
            score_crop = score_text(plate_text)
            if score_crop < 5.0:
                # Crop OCR text was weak or not a valid plate. Run OCR on the full vehicle image
                full_text, full_conf, full_engine = run_ocr(image_path)
                score_full = score_text(full_text)
                
                if score_full > score_crop and score_full >= 4.0:
                    plate_text = full_text
                    ocr_conf = full_conf
                    ocr_engine = full_engine
            
            # Combine YOLO confidence & OCR confidence
            total_confidence = round((best_conf + ocr_conf) / 2 * 100, 2) if ocr_conf > 0 else round(best_conf * 100, 2)
            
            # If OCR failed to extract text but plate was detected
            if not plate_text:
                plate_text = "UNKNOWN"
                total_confidence = round(best_conf * 100, 2)

            relative_cropped_path = os.path.relpath(cropped_path, base_dir).replace('\\', '/')

            print(json.dumps({
                "success": True,
                "plate_text": plate_text,
                "confidence": total_confidence,
                "cropped_path": relative_cropped_path,
                "detection_confidence": round(best_conf * 100, 2),
                "ocr_confidence": round(ocr_conf * 100, 2),
                "ocr_engine": ocr_engine
            }))
        else:
            # Fallback when no license plate is found by YOLOv8
            crop_h, crop_w = int(h * 0.3), int(w * 0.5)
            y1_crop, x1_crop = int(h * 0.4), int(w * 0.25)
            cropped_img = img[y1_crop:y1_crop+crop_h, x1_crop:x1_crop+crop_w]
            cv2.imwrite(cropped_path, cropped_img)
            
            # Run OCR on the full vehicle image first
            plate_text, ocr_conf, ocr_engine = run_ocr(image_path)
            score_full = score_text(plate_text)
            
            # If full image OCR failed, run OCR on the fallback crop
            if score_full < 4.0:
                crop_text, crop_conf, crop_engine = run_ocr(cropped_path)
                score_crop = score_text(crop_text)
                if score_crop > score_full:
                    plate_text = crop_text
                    ocr_conf = crop_conf
                    ocr_engine = crop_engine
                
            relative_cropped_path = os.path.relpath(cropped_path, base_dir).replace('\\', '/')
            
            if plate_text and len(plate_text) >= 4:
                print(json.dumps({
                    "success": True,
                    "plate_text": plate_text,
                    "confidence": round(ocr_conf * 100, 2),
                    "cropped_path": relative_cropped_path,
                    "detection_confidence": 0.0,
                    "ocr_confidence": round(ocr_conf * 100, 2),
                    "ocr_engine": ocr_engine
                }))
            else:
                print(json.dumps({
                    "success": False,
                    "error": "No license plate detected",
                    "fallback_cropped_path": relative_cropped_path
                }))

    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
