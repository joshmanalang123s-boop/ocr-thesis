# GPU-Accelerated YOLOv8 + PaddleOCR Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the per-request `shell_exec`'d `detect_plate.py` CPU pipeline with a persistent GPU-backed FastAPI backend (YOLOv8 + PaddleOCR only), auto-started by Laravel, reporting YOLO/Paddle/total timing.

**Architecture:** Pure logic (sanitize/score/line-merging, timing) lives in small testable modules. Model-loading and inference live in a persistent FastAPI process (`scripts/backend_server.py`) that loads YOLOv8 + PaddleOCR once at startup on GPU (CPU fallback if unavailable). `PlateOcrController::processPlateImage()` health-checks the backend, spawns it if down, then calls it over HTTP instead of shelling out per request.

**Tech Stack:** Python 3.13, FastAPI + uvicorn, ultralytics (YOLOv8), paddleocr 3.7 (PaddleX-based, `device=` kwarg), torch 2.9.1+cu126, paddlepaddle-gpu 3.3.1 (cu126), pytest (new dev dependency for pure-logic unit tests), PHP/Laravel 12 (`Illuminate\Support\Facades\Http`).

Full context: `docs/superpowers/specs/2026-08-17-gpu-ocr-backend-design.md`

---

### Task 1: Telemetry module

**Files:**
- Create: `scripts/telemetry.py`
- Test: `scripts/tests/test_telemetry.py`

- [ ] **Step 1: Install pytest**

Run: `py -m pip install pytest`
Expected: installs successfully, no errors.

- [ ] **Step 2: Write the failing tests**

Create `scripts/tests/test_telemetry.py`:

```python
import os
import sys
import time

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from telemetry import Telemetry


def test_phase_records_elapsed_ms():
    telem = Telemetry()
    with telem.phase("yolo"):
        time.sleep(0.01)
    summary = telem.summary()
    assert "yolo_ms" in summary
    assert summary["yolo_ms"] >= 10.0


def test_summary_includes_total_ms():
    telem = Telemetry()
    with telem.phase("yolo"):
        time.sleep(0.005)
    with telem.phase("paddle"):
        time.sleep(0.005)
    summary = telem.summary()
    assert set(summary.keys()) == {"yolo_ms", "paddle_ms", "total_ms"}
    # total wall-clock must cover at least the two phases (small tolerance for perf_counter jitter)
    assert summary["total_ms"] >= summary["yolo_ms"] + summary["paddle_ms"] - 2


def test_multiple_distinct_phases_tracked_independently():
    telem = Telemetry()
    with telem.phase("a"):
        time.sleep(0.001)
    with telem.phase("b"):
        time.sleep(0.001)
    summary = telem.summary()
    assert summary["a_ms"] > 0
    assert summary["b_ms"] > 0


def test_repeated_phase_name_accumulates():
    telem = Telemetry()
    with telem.phase("paddle"):
        time.sleep(0.005)
    with telem.phase("paddle"):
        time.sleep(0.005)
    summary = telem.summary()
    assert summary["paddle_ms"] >= 10.0
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `py -m pytest scripts/tests/test_telemetry.py -v`
Expected: FAIL with `ModuleNotFoundError: No module named 'telemetry'`

- [ ] **Step 4: Write the implementation**

Create `scripts/telemetry.py`:

```python
import time
from collections import OrderedDict


class Telemetry:
    """Tracks named timing phases (e.g. yolo, paddle) plus overall wall-clock total.

    Calling phase() more than once with the same name accumulates elapsed time
    under that name, since a single request can call the same phase (e.g.
    PaddleOCR) more than once (crop OCR, then full-image OCR fallback).
    """

    def __init__(self):
        self._phases = OrderedDict()
        self._start = time.perf_counter()

    def phase(self, name):
        return _Phase(self, name)

    def _record(self, name, elapsed_ms):
        self._phases[name] = self._phases.get(name, 0.0) + elapsed_ms

    def summary(self):
        total_ms = round((time.perf_counter() - self._start) * 1000, 2)
        result = {f"{name}_ms": round(ms, 2) for name, ms in self._phases.items()}
        result["total_ms"] = total_ms
        return result

    def print_report(self, prefix="[telemetry]"):
        parts = [f"{k}={v}" for k, v in self.summary().items()]
        print(f"{prefix} " + " ".join(parts))


class _Phase:
    def __init__(self, telemetry, name):
        self._telemetry = telemetry
        self._name = name
        self._start = None

    def __enter__(self):
        self._start = time.perf_counter()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        elapsed_ms = (time.perf_counter() - self._start) * 1000
        self._telemetry._record(self._name, elapsed_ms)
        return False
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `py -m pytest scripts/tests/test_telemetry.py -v`
Expected: 4 passed

- [ ] **Step 6: Commit**

```bash
git add scripts/telemetry.py scripts/tests/test_telemetry.py
git commit -m "feat: add Telemetry phase-timing helper"
```

---

### Task 2: Pure OCR-logic module (`plate_pipeline.py`, no models yet)

Extracts the parts of `detect_plate.py` that don't touch YOLO/PaddleOCR objects, drops the EasyOCR branch of `get_merged_lines` (EasyOCR is being removed per the approved spec).

**Files:**
- Create: `scripts/plate_pipeline.py`
- Test: `scripts/tests/test_plate_pipeline.py`

- [ ] **Step 1: Write the failing tests**

Create `scripts/tests/test_plate_pipeline.py`:

```python
import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from plate_pipeline import sanitize_plate_number, score_text, get_merged_lines


def test_sanitize_plate_number_strips_symbols_and_uppercases():
    assert sanitize_plate_number("abc-123!!") == "ABC 123"


def test_sanitize_plate_number_collapses_whitespace_and_dashes():
    assert sanitize_plate_number("ab   --  12") == "AB 12"


def test_score_text_rewards_letter_number_mix_in_plate_length_range():
    assert score_text("ABC1234") > score_text("1234567890123")


def test_score_text_empty_or_symbols_only_is_zero():
    assert score_text("") == 0.0
    assert score_text("###") == 0.0


def test_get_merged_lines_groups_same_row_left_to_right():
    # Two blocks on the same row, given out of left-to-right order
    result_data = [
        [[[50, 10], [90, 10], [90, 30], [50, 30]], ("456", 0.9)],
        [[[0, 10], [40, 10], [40, 30], [0, 30]], ("ABC", 0.8)],
    ]
    merged = get_merged_lines(result_data, img_height=40)
    assert len(merged) == 1
    text, conf = merged[0]
    assert text == "ABC 456"
    assert round(conf, 2) == 0.85


def test_get_merged_lines_separates_distinct_rows():
    result_data = [
        [[[0, 0], [40, 0], [40, 20], [0, 20]], ("TOP", 0.9)],
        [[[0, 100], [40, 100], [40, 120], [0, 120]], ("BOTTOM", 0.9)],
    ]
    merged = get_merged_lines(result_data, img_height=140)
    assert len(merged) == 2
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `py -m pytest scripts/tests/test_plate_pipeline.py -v`
Expected: FAIL with `ModuleNotFoundError: No module named 'plate_pipeline'`

- [ ] **Step 3: Write the implementation**

Create `scripts/plate_pipeline.py`:

```python
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


def get_merged_lines(paddle_result, img_height):
    """Group PaddleOCR text blocks horizontally and merge them left-to-right.

    paddle_result format: [ [ [[x1,y1],[x2,y2],[x3,y3],[x4,y4]], ('text', conf) ], ... ]
    """
    lines = []
    y_threshold = max(30, img_height * 0.15)

    for line in paddle_result:
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

        added = False
        for group in lines:
            if abs(group['y_center'] - y_center) < y_threshold:
                group['items'].append({'text': val, 'x_min': x_min, 'conf': conf})
                group['y_center'] = (group['y_center'] * (len(group['items']) - 1) + y_center) / len(group['items'])
                added = True
                break
        if not added:
            lines.append({
                'y_center': y_center,
                'items': [{'text': val, 'x_min': x_min, 'conf': conf}]
            })

    merged_texts = []
    for group in lines:
        sorted_items = sorted(group['items'], key=lambda x: x['x_min'])
        merged_text = " ".join([item['text'] for item in sorted_items])
        avg_conf = sum([item['conf'] for item in sorted_items]) / len(sorted_items)
        merged_texts.append((merged_text, avg_conf))

    return merged_texts
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `py -m pytest scripts/tests/test_plate_pipeline.py -v`
Expected: 6 passed

- [ ] **Step 5: Commit**

```bash
git add scripts/plate_pipeline.py scripts/tests/test_plate_pipeline.py
git commit -m "feat: extract pure plate OCR text-scoring/merging logic, drop EasyOCR"
```

---

### Task 3: Install GPU-enabled torch + paddlepaddle-gpu

No code yet — just gets the environment to where GPU inference is actually possible. Already verified both wheels exist for this machine (RTX 3060, CUDA 13.0 driver, Python 3.13) via `pip index versions` during design.

**Files:** none (environment only)

- [ ] **Step 1: Install GPU torch (replaces the currently-broken CPU build)**

Run:
```bash
py -m pip install torch==2.9.1+cu126 --index-url https://download.pytorch.org/whl/cu126 --force-reinstall
```
Expected: completes, installs `torch-2.9.1+cu126`.

- [ ] **Step 2: Install paddlepaddle-gpu**

Run:
```bash
py -m pip install paddlepaddle-gpu==3.3.1 -i https://www.paddlepaddle.org.cn/packages/stable/cu126/ --force-reinstall
```
Expected: completes, installs `paddlepaddle-gpu-3.3.1`.

- [ ] **Step 3: Verify both report CUDA/GPU available**

Run:
```bash
py -c "
import torch
print('torch cuda available:', torch.cuda.is_available())
print('torch device name:', torch.cuda.get_device_name(0) if torch.cuda.is_available() else 'n/a')
import paddle
print('paddle cuda compiled:', paddle.device.is_compiled_with_cuda())
"
```
Expected: `torch cuda available: True`, device name shows the RTX 3060, `paddle cuda compiled: True`.

If either prints `False`: stop and re-check the driver/CUDA version against the installed wheel's CUDA build (do not proceed to Task 4-5 until this passes — the whole point of the plan is GPU execution, and the code has a CPU fallback specifically so it stays working while this gets sorted out).

- [ ] **Step 4: Re-run Task 1 and Task 2 tests to confirm the reinstall didn't break anything unrelated**

Run: `py -m pytest scripts/tests -v`
Expected: 10 passed

No commit needed — this task only changes installed packages, not files in the repo (`pip freeze`/`requirements.txt` isn't part of this project's tracked setup).

---

### Task 4: Model-invoking pipeline functions

Adds the YOLO+PaddleOCR-calling functions to `plate_pipeline.py`. These need real models and a real image, so they're verified manually against a sample image already in `storage/app/public/plates/` rather than mocked — mocking ultralytics/PaddleOCR objects would test the mock, not the pipeline.

**Files:**
- Modify: `scripts/plate_pipeline.py` (append to end of file)

- [ ] **Step 1: Append `run_paddle_ocr` and `detect_and_recognize` to `scripts/plate_pipeline.py`**

Add to the end of `scripts/plate_pipeline.py`:

```python
def run_paddle_ocr(paddle_ocr, image_path, telemetry):
    """Run PaddleOCR on an image path, return (sanitized_text, confidence)."""
    import cv2

    img = cv2.imread(image_path)
    if img is None:
        return "", 0.0
    img_height = img.shape[0]

    with telemetry.phase("paddle"):
        try:
            result = paddle_ocr.ocr(image_path)
        except Exception:
            import traceback
            traceback.print_exc(file=sys.stderr)
            result = None

    text = ""
    confidence = 0.0
    if result and len(result) > 0 and result[0] is not None:
        merged_lines = get_merged_lines(result[0], img_height)
        if merged_lines:
            best_text, best_conf, best_score = "", 0.0, -999.0
            for line_text, line_conf in merged_lines:
                scr = score_text(line_text)
                if scr > best_score:
                    best_score, best_text, best_conf = scr, line_text, line_conf
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
        results = yolo_model(img, device=device, verbose=False)[0]

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
        pad_w = int((x2 - x1) * 0.08)
        pad_h = int((y2 - y1) * 0.08)
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
```

- [ ] **Step 2: Manually verify against a real sample image**

Run (adjust the filename to whatever exists under `storage/app/public/plates/` — list it first with `ls storage/app/public/plates/`):

```bash
py -c "
import os, sys
os.environ.setdefault('FLAGS_use_mkldnn', '0')
os.environ.setdefault('PADDLE_PDX_ENABLE_MKLDNN_BYDEFAULT', '0')
sys.path.insert(0, 'scripts')
import torch
from ultralytics import YOLO
from paddleocr import PaddleOCR
import plate_pipeline
from telemetry import Telemetry

device = 'cuda:0' if torch.cuda.is_available() else 'cpu'
model_path = 'storage/app/models/yolov8n_plate.pt'
plate_pipeline.download_model(model_path)
yolo_model = YOLO(model_path)
yolo_model.to(device)
paddle_ocr = PaddleOCR(lang='en', device=('gpu:0' if device == 'cuda:0' else 'cpu'))

telemetry = Telemetry()
result = plate_pipeline.detect_and_recognize(
    yolo_model, paddle_ocr, 'storage/app/public/plates/<SAMPLE_FILE>.jpg', os.path.abspath('.'), telemetry, device
)
print(result)
"
```
Expected: a dict with `"success": True` (or a reasonable `False` for a bad image), `"telemetry"` containing `yolo_ms`, `paddle_ms`, `total_ms`, all > 0.

- [ ] **Step 3: Re-run the full pytest suite to confirm the pure-function tests still pass unchanged**

Run: `py -m pytest scripts/tests -v`
Expected: 10 passed

- [ ] **Step 4: Commit**

```bash
git add scripts/plate_pipeline.py
git commit -m "feat: add GPU-aware YOLOv8 detect + PaddleOCR recognize pipeline"
```

---

### Task 5: FastAPI backend server

**Files:**
- Create: `scripts/backend_server.py`

- [ ] **Step 1: Write `scripts/backend_server.py`**

```python
import os
import sys
import threading
import logging

os.environ.setdefault("FLAGS_use_mkldnn", "0")
os.environ.setdefault("PADDLE_PDX_ENABLE_MKLDNN_BYDEFAULT", "0")

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from fastapi import FastAPI
from pydantic import BaseModel
import uvicorn

from telemetry import Telemetry
import plate_pipeline

logging.basicConfig(level=logging.INFO, format="%(asctime)s [backend] %(message)s")
logger = logging.getLogger("backend")

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
MODEL_PATH = os.path.join(BASE_DIR, "storage", "app", "models", "yolov8n_plate.pt")

app = FastAPI()
_inference_lock = threading.Lock()
_state = {}


def _load_models():
    import torch
    from ultralytics import YOLO
    from paddleocr import PaddleOCR

    device = "cpu"
    try:
        if torch.cuda.is_available():
            device = "cuda:0"
    except Exception:
        logger.warning("torch.cuda.is_available() check failed, defaulting to CPU", exc_info=True)

    plate_pipeline.download_model(MODEL_PATH)

    try:
        yolo_model = YOLO(MODEL_PATH)
        yolo_model.to(device)
        paddle_device = "gpu:0" if device == "cuda:0" else "cpu"
        paddle_ocr = PaddleOCR(lang="en", device=paddle_device)
    except Exception:
        logger.exception("GPU model load failed, falling back to CPU")
        device = "cpu"
        yolo_model = YOLO(MODEL_PATH)
        yolo_model.to("cpu")
        paddle_ocr = PaddleOCR(lang="en", device="cpu")

    logger.info("Models loaded. device=%s", device)
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
```

- [ ] **Step 2: Start it manually and check the startup log**

Run (foreground, separate terminal):
```bash
py scripts/backend_server.py
```
Expected: log line `Models loaded. device=cuda:0` (or `device=cpu` with a preceding "GPU model load failed" warning if GPU init fails — should not crash either way), then uvicorn's `Uvicorn running on http://127.0.0.1:8600`.

- [ ] **Step 3: Verify `/health`**

Run (in another terminal, while step 2's server is running):
```bash
curl http://127.0.0.1:8600/health
```
Expected: `{"status":"ok","device":"cuda:0"}`

- [ ] **Step 4: Verify `/detect` against a real sample image**

Run (replace `<SAMPLE_FILE>` and use an absolute path):
```bash
curl -X POST http://127.0.0.1:8600/detect \
  -H "Content-Type: application/json" \
  -d "{\"image_path\": \"D:\\\\xampp\\\\htdocs\\\\ocr1 (2)\\\\ocr1\\\\ocr2\\\\storage\\\\app\\\\public\\\\plates\\\\<SAMPLE_FILE>.jpg\"}"
```
Expected: JSON response with `plate_text`, `confidence`, `telemetry: {yolo_ms, paddle_ms, total_ms}`. The backend's own terminal (from step 2) should print a `[telemetry] yolo_ms=... paddle_ms=... total_ms=...` line.

- [ ] **Step 5: Stop the manual server (Ctrl+C) and commit**

```bash
git add scripts/backend_server.py
git commit -m "feat: add persistent FastAPI backend loading YOLOv8+PaddleOCR once on GPU"
```

---

### Task 6: Slim `detect_plate.py` to a CLI wrapper

Keeps a standalone command-line entry point for manual testing/debugging, no longer used by PHP after Task 7.

**Files:**
- Modify: `scripts/detect_plate.py` (full rewrite)

- [ ] **Step 1: Replace the contents of `scripts/detect_plate.py`**

```python
import sys
import os
import json
import io

os.environ.setdefault("FLAGS_use_mkldnn", "0")
os.environ.setdefault("PADDLE_PDX_ENABLE_MKLDNN_BYDEFAULT", "0")

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import plate_pipeline
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
        import torch
        from ultralytics import YOLO
        from paddleocr import PaddleOCR

        device = "cuda:0" if torch.cuda.is_available() else "cpu"
        paddle_device = "gpu:0" if device == "cuda:0" else "cpu"

        plate_pipeline.download_model(model_path)
        yolo_model = YOLO(model_path)
        yolo_model.to(device)
        paddle_ocr = PaddleOCR(lang="en", device=paddle_device)

        telemetry = Telemetry()
        result = plate_pipeline.detect_and_recognize(
            yolo_model, paddle_ocr, image_path, base_dir, telemetry, device
        )
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
```

- [ ] **Step 2: Manually verify standalone CLI run**

Run (replace `<SAMPLE_FILE>`):
```bash
py scripts/detect_plate.py "storage/app/public/plates/<SAMPLE_FILE>.jpg"
```
Expected: prints one JSON line to stdout with `plate_text`, `confidence`, and a `telemetry` object with `yolo_ms`/`paddle_ms`/`total_ms`.

- [ ] **Step 3: Commit**

```bash
git add scripts/detect_plate.py
git commit -m "refactor: slim detect_plate.py to a CLI wrapper over plate_pipeline"
```

---

### Task 7: Wire Laravel to the backend

**Files:**
- Modify: `app/Http/Controllers/PlateOcrController.php`

- [ ] **Step 1: Add the `Http` facade import**

In `app/Http/Controllers/PlateOcrController.php`, change:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\PlateEntry;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
```

to:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Schema\Blueprint;
use App\Models\PlateEntry;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
```

- [ ] **Step 2: Replace `processPlateImage()` and add `ensureBackendRunning()`**

Replace the existing `processPlateImage` method (lines 20-88 in the original file) with:

```php
    /**
     * Check if the GPU OCR backend is up; if not, spawn it and wait for it to become healthy.
     *
     * @return bool True if the backend is reachable within the timeout.
     */
    private function ensureBackendRunning()
    {
        $healthUrl = 'http://127.0.0.1:8600/health';

        try {
            $response = Http::timeout(1)->get($healthUrl);
            if ($response->successful()) {
                return true;
            }
        } catch (\Exception $e) {
            // Not reachable yet, fall through to spawn it.
        }

        $scriptPath = base_path('scripts/backend_server.py');
        $logPath = storage_path('logs/gpu_backend.log');

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['file', $logPath, 'a'],
            2 => ['file', $logPath, 'a'],
        ];

        $process = proc_open(
            ['py', $scriptPath],
            $descriptorspec,
            $pipes,
            base_path(),
            null,
            ['bypass_shell' => true]
        );

        if (is_resource($process)) {
            fclose($pipes[0]);
        }

        // First boot loads YOLO + PaddleOCR onto the GPU, give it up to 90s.
        $deadline = microtime(true) + 90;
        while (microtime(true) < $deadline) {
            usleep(1000000);
            try {
                $response = Http::timeout(1)->get($healthUrl);
                if ($response->successful()) {
                    return true;
                }
            } catch (\Exception $e) {
                // Still starting up, keep polling.
            }
        }

        return false;
    }

    /**
     * Run YOLOv8 and PaddleOCR detection on a given image file path via the GPU backend.
     *
     * @param string $absolutePath Absolute path to the image
     * @return array
     */
    private function processPlateImage($absolutePath)
    {
        @set_time_limit(120);

        $startTime = microtime(true);

        if (!$this->ensureBackendRunning()) {
            $duration = round(microtime(true) - $startTime, 2);
            $errorMsg = 'GPU OCR backend is not available.';
            file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
            file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
            file_put_contents('php://stderr', "Time Elapsed: {$duration} seconds\n");
            file_put_contents('php://stderr', "Detection Result: FAILED - {$errorMsg}\n");
            file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        }

        try {
            $response = Http::timeout(30)->post('http://127.0.0.1:8600/detect', [
                'image_path' => $absolutePath,
            ]);

            $duration = round(microtime(true) - $startTime, 2);

            if (!$response->successful()) {
                $errorMsg = 'Backend returned HTTP ' . $response->status();
                file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
                file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
                file_put_contents('php://stderr', "Time Elapsed: {$duration} seconds\n");
                file_put_contents('php://stderr', "Detection Result: FAILED - {$errorMsg}\n");
                file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");
                return [
                    'success' => false,
                    'error' => $errorMsg,
                ];
            }

            $result = $response->json();

            $resultText = "Unknown";
            if ($result && isset($result['success']) && $result['success']) {
                $resultText = "Plate: " . $result['plate_text'] . " (Conf: " . $result['confidence'] . "%, Engine: " . ($result['ocr_engine'] ?? 'paddleocr') . ")";
            } else {
                $resultText = "FAILED - " . ($result['error'] ?? 'Unknown Error');
            }

            $telemetry = $result['telemetry'] ?? [];
            $yoloMs = $telemetry['yolo_ms'] ?? 'n/a';
            $paddleMs = $telemetry['paddle_ms'] ?? 'n/a';
            $totalMs = $telemetry['total_ms'] ?? 'n/a';

            file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
            file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
            file_put_contents('php://stderr', "Time Elapsed: {$duration} seconds\n");
            file_put_contents('php://stderr', "YOLO: {$yoloMs}ms | Paddle: {$paddleMs}ms | Total: {$totalMs}ms\n");
            file_put_contents('php://stderr', "Detection Result: {$resultText}\n");
            file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");

            return $result;
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
            file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
            file_put_contents('php://stderr', "Time Elapsed: {$duration} seconds\n");
            file_put_contents('php://stderr', "Detection Exception: " . $e->getMessage() . "\n");
            file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");
            return [
                'success' => false,
                'error' => 'Exception during plate detection: ' . $e->getMessage(),
            ];
        }
    }
```

- [ ] **Step 3: Confirm PHP syntax is valid**

Run: `php -l app/Http/Controllers/PlateOcrController.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Manual verification — stop any manually-running backend from Task 5/6, then use the real app**

1. Make sure nothing is listening on port 8600 (`curl http://127.0.0.1:8600/health` should fail/connection-refused).
2. Open the entry-camera page in the app, capture/upload a plate image.
3. Confirm: the request succeeds, a `PlateEntry` row is created, and `storage/logs/gpu_backend.log` shows the backend's startup log (`Models loaded. device=...`).
4. Check PHP's stderr output (Laravel/XAMPP error log, or the terminal running `php artisan serve` if that's how it's served) for the `>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<` block including the new `YOLO: ...ms | Paddle: ...ms | Total: ...ms` line.
5. Submit a second image — this time `/health` should already succeed immediately (no spawn), and the response should be noticeably faster than the first (no model load).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PlateOcrController.php
git commit -m "feat: call GPU OCR backend over HTTP instead of shell_exec per request"
```

---

### Task 8: End-to-end smoke test and wrap-up

**Files:** none (verification only)

- [ ] **Step 1: Full pytest suite**

Run: `py -m pytest scripts/tests -v`
Expected: 10 passed

- [ ] **Step 2: Exit-gate flow**

With the backend already running from Task 7, use the exit-camera page (`detectExit`) against a plate that was entered earlier in testing. Confirm it matches the existing `PlateEntry`, computes duration/fee, and the stderr telemetry block appears again.

- [ ] **Step 3: Backend-down fallback**

Stop the backend process (find it via Task Manager or `netstat -ano | findstr 8600` -> `taskkill /PID <pid> /F`), then temporarily block it from restarting by renaming `scripts/backend_server.py` to `scripts/backend_server.py.bak`. Submit a plate image through the app.
Expected: `ensureBackendRunning()` fails after ~90s, `processPlateImage` returns `success: false`, and the existing placeholder-plate fallback in `detect()` (the one already in the codebase, format `ABC-1234` etc.) kicks in — the app does not hard-error.
Rename `scripts/backend_server.py.bak` back to `scripts/backend_server.py` afterward.

- [ ] **Step 4: Confirm working tree is clean and everything is committed**

Run: `git status`
Expected: `nothing to commit, working tree clean` (aside from anything unrelated already present before this plan started).
