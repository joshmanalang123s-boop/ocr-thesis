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
    # Remove extra spaces and keep alphanumeric characters, spaces, and dashes
    cleaned = re.sub(r'[^a-zA-Z0-9\s-]', '', text)
    # Condense multiple spaces/dashes
    cleaned = re.sub(r'[\s-]+', ' ', cleaned)
    return cleaned.strip().upper()

def run_ocr(image_path):
    """Run PaddleOCR on the cropped plate image, with EasyOCR as a fallback."""
    text = ""
    confidence = 0.0
    engine_used = "none"

    # 1. Try PaddleOCR
    try:
        import logging
        logging.getLogger("ppocr").setLevel(logging.ERROR)
        from paddleocr import PaddleOCR
        # Initialize PaddleOCR (downloads models on first run)
        ocr = PaddleOCR(use_angle_cls=False, lang='en')
        ocr_result = ocr.ocr(image_path)
        
        if ocr_result and len(ocr_result) > 0 and ocr_result[0] is not None:
            texts = []
            confidences = []
            for line in ocr_result[0]:
                if line and len(line) > 1:
                    texts.append(line[1][0])
                    confidences.append(line[1][1])
            if texts:
                text = " ".join(texts)
                confidence = sum(confidences) / len(confidences)
                engine_used = "paddleocr"
    except Exception as e:
        print(f"PaddleOCR failed/not available: {e}. Falling back to EasyOCR...", file=sys.stderr)

    # 2. Try EasyOCR as a fallback
    if not text:
        try:
            import easyocr
            # Initialize EasyOCR reader (downloads models on first run)
            reader = easyocr.Reader(['en'], gpu=False)
            
            # Load and convert image to grayscale (2D array) to avoid shape unpacking bug in EasyOCR
            img = cv2.imread(image_path)
            if img is not None:
                gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
                ocr_result = reader.readtext(gray)
            else:
                ocr_result = reader.readtext(image_path)
            
            if ocr_result:
                texts = []
                confidences = []
                for bbox, val, conf in ocr_result:
                    texts.append(val)
                    confidences.append(conf)
                if texts:
                    text = " ".join(texts)
                    confidence = sum(confidences) / len(confidences)
                    engine_used = "easyocr"
        except Exception as e:
            import traceback
            traceback.print_exc(file=sys.stderr)
            print(f"EasyOCR failed/not available: {e}.", file=sys.stderr)

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
        results = model(img, verbose=False)[0]

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
            
            # Pad the bounding box slightly (5%) for cleaner OCR
            pad_w = int((x2 - x1) * 0.08)
            pad_h = int((y2 - y1) * 0.08)
            
            x1_crop = max(0, x1 - pad_w)
            y1_crop = max(0, y1 - pad_h)
            x2_crop = min(w, x2 + pad_w)
            y2_crop = min(h, y2 + pad_h)

            cropped_img = img[y1_crop:y2_crop, x1_crop:x2_crop]
            cv2.imwrite(cropped_path, cropped_img)
            
            # Run OCR on the cropped plate
            plate_text, ocr_conf, ocr_engine = run_ocr(cropped_path)
            
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
            # Make a cropped thumbnail of the middle area of the car image to display
            crop_h, crop_w = int(h * 0.3), int(w * 0.5)
            y1_crop, x1_crop = int(h * 0.4), int(w * 0.25)
            cropped_img = img[y1_crop:y1_crop+crop_h, x1_crop:x1_crop+crop_w]
            cv2.imwrite(cropped_path, cropped_img)
            
            # Run OCR on the middle crop just in case
            plate_text, ocr_conf, ocr_engine = run_ocr(cropped_path)
            relative_cropped_path = os.path.relpath(cropped_path, base_dir).replace('\\', '/')
            
            if plate_text and len(plate_text) >= 4:
                # If OCR found something in the fallback region
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
                # Genuine failure
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
