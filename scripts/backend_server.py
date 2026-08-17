import os
import sys
import threading
import logging

os.environ.setdefault("FLAGS_use_mkldnn", "0")
os.environ.setdefault("PADDLE_PDX_ENABLE_MKLDNN_BYDEFAULT", "0")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# plate_pipeline imports torch first (see comment there) so paddle's cuDNN
# falls back to torch's already-loaded copy instead of conflicting with it.
import plate_pipeline
import torch

from fastapi import FastAPI
from pydantic import BaseModel
import uvicorn
import numpy as np

from telemetry import Telemetry

logging.basicConfig(level=logging.INFO, format="%(asctime)s [backend] %(message)s")
logger = logging.getLogger("backend")

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
MODEL_PATH = os.path.join(BASE_DIR, "storage", "app", "models", "yolov8n_plate.pt")

app = FastAPI()
_inference_lock = threading.Lock()
_state = {}


def _load_models():
    from ultralytics import YOLO
    from paddleocr import PaddleOCR

    device = "cpu"
    try:
        if torch.cuda.is_available():
            device = "cuda:0"
    except Exception:
        logger.warning("torch.cuda.is_available() check failed, defaulting to CPU", exc_info=True)

    plate_pipeline.download_model(MODEL_PATH)

    paddle_device = "gpu:0" if device == "cuda:0" else "cpu"
    try:
        yolo_model = YOLO(MODEL_PATH)
        yolo_model.to(device)
        # Doc-orientation/unwarping/textline-orientation are extra PaddleX
        # pipeline stages paddleocr 3.x enables by default; disabling them
        # is the 3.x equivalent of the old use_angle_cls=False - a fixed
        # gate camera doesn't need per-image rotation correction, and
        # skipping them means only the det+rec models load.
        paddle_ocr = PaddleOCR(
            lang="en",
            device=paddle_device,
            use_doc_orientation_classify=False,
            use_doc_unwarping=False,
            use_textline_orientation=False,
        )
    except Exception:
        logger.exception("GPU model load failed, falling back to CPU")
        device = "cpu"
        yolo_model = YOLO(MODEL_PATH)
        yolo_model.to("cpu")
        paddle_ocr = PaddleOCR(
            lang="en",
            device="cpu",
            use_doc_orientation_classify=False,
            use_doc_unwarping=False,
            use_textline_orientation=False,
        )

    logger.info("Models loaded. device=%s", device)

    # First CUDA call on a freshly loaded model pays a JIT/kernel-compile
    # tax (seconds, not ms). Pay it once here at startup instead of on
    # whichever real request happens to arrive first.
    warmup_img = np.zeros((640, 640, 3), dtype=np.uint8)
    yolo_model(warmup_img, device=device, verbose=False, conf=0.05)
    logger.info("YOLO warmup complete.")

    return device, yolo_model, paddle_ocr


@app.on_event("startup")
def startup():
    device, yolo_model, paddle_ocr = _load_models()
    _state["device"] = device
    _state["yolo_model"] = yolo_model
    _state["paddle_ocr"] = paddle_ocr


class DetectRequest(BaseModel):
    image_path: str


@app.get("/health")
def health():
    return {"status": "ok", "device": _state.get("device", "unknown")}


@app.post("/detect")
def detect(req: DetectRequest):
    telemetry = Telemetry()
    with _inference_lock:
        result = plate_pipeline.detect_and_recognize(
            _state["yolo_model"],
            _state["paddle_ocr"],
            req.image_path,
            BASE_DIR,
            telemetry,
            _state["device"],
        )
    telemetry.print_report()
    return result


if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8600)
