import sys
import os
import json
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import plate_pipeline
import torch
from telemetry import Telemetry


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Missing input image path argument"}))
        sys.exit(1)

    image_path = sys.argv[1]
    if not os.path.exists(image_path):
        print(json.dumps({"success": False, "error": f"Image file not found: {image_path}"}))
        sys.exit(1)

    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    model_path = os.path.join(base_dir, "storage", "app", "models", "yolov8n_plate.pt")

    try:
        from ultralytics import YOLO
        from fast_plate_ocr import LicensePlateRecognizer

        device = "cuda:0" if torch.cuda.is_available() else "cpu"

        plate_pipeline.download_model(model_path)
        yolo_model = YOLO(model_path)
        yolo_model.to(device)
        ocr_recognizer = LicensePlateRecognizer("cct-s-v2-global-model", device="auto")

        telemetry = Telemetry()
        result = plate_pipeline.detect_and_recognize(
            yolo_model, ocr_recognizer, image_path, base_dir, telemetry, device
        )
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
