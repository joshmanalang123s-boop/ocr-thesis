# GPU-accelerated YOLOv8 + PaddleOCR backend with telemetry

Date: 2026-08-17
Status: Approved

## Problem

`scripts/detect_plate.py` runs on CPU and is invoked via `shell_exec` from
`PlateOcrController::processPlateImage()` for every single request. Each
invocation spawns a fresh Python process that reloads YOLOv8 and
EasyOCR/PaddleOCR from disk before doing any work. There is no GPU usage and
no visibility into where time is spent (detection vs OCR).

Machine has an NVIDIA RTX 3060 (6GB VRAM, driver supports CUDA 13.0).
Installed `torch` (2.9.1+cpu) and `paddle` (3.3.1) are both CPU-only builds;
the installed torch build also fails to load (`shm.dll` load error), so it
needs reinstalling regardless of the GPU work.

## Goals

- YOLOv8 and PaddleOCR inference run on GPU.
- Models load once and stay resident in memory (persistent backend) instead
  of reloading per request.
- Telemetry: YOLO time, PaddleOCR time, and total pipeline time, surfaced in
  the JSON response, logged to PHP's stderr (existing log style), and printed
  in the Python backend's own terminal.
- Backend auto-starts from Laravel if not already running.
- Drop EasyOCR; pipeline becomes YOLOv8 (detection) + PaddleOCR (recognition)
  only, matching what was asked for.
- App keeps working (degraded) if GPU or the backend is unavailable.

## Amendment (2026-08-18)

Mid-implementation, real uncommitted tuning was found already on disk in
`scripts/detect_plate.py` (committed separately before this refactor touched
it — see `integration_changes.md`): a temporary-plate-word blacklist in
`sanitize_plate_number`, font-height filtering in `get_merged_lines`,
`conf=0.05` on YOLO detection, and 18%/10% crop padding (up from 8%/8%).
All of this is preserved in the extracted `plate_pipeline.py` — it was
tuned against real failure cases (e.g. the clipped `N` in `NDP 9668`) and
this refactor is not the place to re-litigate it.

The original script also ran EasyOCR before PaddleOCR, justified in
`integration_changes.md` as "bypasses the PaddleOCR CPU startup overhead on
success." That reason no longer applies once models are loaded once in a
persistent backend process — so EasyOCR is still dropped per the earlier
decision, but for a different, now-explicit reason.

**VLM fallback (gemma4:e2b via Ollama) — spiked, rejected.** Installed
Ollama, pulled `gemma4:e2b` (7.2GB), ran one real inference against a
cropped plate image with a known-correct ground truth ("CAR 5OS", per
PaddleOCR on the same file). Measured via Ollama's own response metadata:

| Phase | Time |
|---|---|
| Model load (one-time, cold) | ~100.1s |
| Prompt/image eval | ~79.9s |
| Generation (646 tokens — the model reasoned at length before answering) | ~32.0s |
| **Total (cold)** | **~212s** |
| **Steady-state (load excluded)** | **~112s** |

`ollama ps` confirmed it ran 100% on GPU (not a CPU-offload artifact) — this
is the model's actual speed on this hardware. And the answer was **wrong**:
`"M R 2 6 5"` against a true value of `"CAR 5OS"`.

~112s (best case, warm) vs. PaddleOCR's measured ~100-600ms is a ~200-1000x
gap. Rejected outright, not just deprioritized to fallback-only — at that
latency it fails even as an occasional fallback for a live gate camera, and
it wasn't more accurate on the one case tested. Not pursuing further
(different quantization, few-shot prompting, a smaller model) without a
specific reason to revisit; noted here so the question doesn't get re-asked
without this data. `ocr_engine` stays `paddleocr`/`none` — no third value.

## Non-goals

- No new OCR engines, no UI changes beyond what's needed to display/log
  telemetry.
- No Windows service/systemd install for the backend — manual understanding
  is "Laravel spawns it on demand if it isn't already up."
- No multi-worker/horizontally-scaled inference. Gate cameras produce low
  concurrency; a single backend process with a lock around inference is
  sufficient.

## Architecture

```
Camera capture (JS) -> Laravel PlateOcrController::processPlateImage()
                          |
                          | 1. GET http://127.0.0.1:8600/health (short timeout)
                          |    not up -> spawn `py scripts/backend_server.py`
                          |    detached, poll /health up to ~90s
                          v
                   FastAPI backend (scripts/backend_server.py)
                   - loads YOLOv8 + PaddleOCR ONCE at import time
                   - device = "cuda" if torch.cuda.is_available() else "cpu"
                   - threading.Lock() around inference calls
                   - POST /detect {image_path} -> JSON + telemetry
                   - GET /health -> {"status": "ok", "device": "cuda"}
                          |
                          v
                   scripts/plate_pipeline.py (shared core logic)
                   scripts/telemetry.py (Timer/phase tracker)
```

## Components

### `scripts/telemetry.py` (new)

Small reusable timing helper, no external deps:

- `Telemetry` class with `.phase(name)` context manager that records elapsed
  wall-clock ms for a named phase into an ordered dict.
- `.summary()` returns `{"<phase>_ms": float, ..., "total_ms": float}`
  (total = wall-clock across all phases measured, not just the sum, so it
  also captures glue code between phases).
- `.print_report()` writes a compact one-line report to stdout, e.g.
  `[telemetry] yolo_ms=142.3 paddle_ms=88.1 total_ms=251.7`.

### `scripts/plate_pipeline.py` (new — extracted from `detect_plate.py`)

Holds the actual detection/OCR logic, parameterized by already-loaded model
instances (so the backend loads models once and passes them in):

- `download_model(model_path)` — unchanged from current script.
- `sanitize_plate_number`, `score_text`, `get_merged_lines` (Paddle branch
  only now) — unchanged logic, moved as-is.
- `run_paddle_ocr(paddle_ocr_instance, image_path, telemetry)` — runs
  PaddleOCR inside `telemetry.phase("paddle")`, returns
  `(text, confidence, engine="paddleocr")`.
- `detect_and_recognize(yolo_model, paddle_ocr_instance, image_path, base_dir, telemetry)`
  — the full pipeline currently in `main()`: YOLO detect (inside
  `telemetry.phase("yolo")`), crop/pad, run OCR on crop, fallback to
  full-image OCR if crop score is weak, assemble the same result dict
  `detect_plate.py` produces today (`success`, `plate_text`, `confidence`,
  `cropped_path`, `detection_confidence`, `ocr_confidence`, `ocr_engine`),
  plus `telemetry.summary()` merged in under `"telemetry"`.

### `scripts/backend_server.py` (new)

FastAPI + uvicorn app:

- On startup: resolve `device = "cuda" if torch.cuda.is_available() else "cpu"`,
  log it, load YOLO model (`YOLO(model_path)`, `.to(device)`) once, load
  `PaddleOCR(...)` once configured for that device. If GPU init fails for any
  reason, catch it, log a warning, and reload on CPU instead of crashing.
- `threading.Lock()` guarding the inference call so concurrent requests
  serialize instead of racing on the same GPU model instances.
- `GET /health` → `{"status": "ok", "device": device}`.
- `POST /detect` body `{"image_path": "<absolute path>"}` → runs
  `plate_pipeline.detect_and_recognize(...)`, returns its result dict as
  JSON, also calls `telemetry.print_report()` to the backend's own stdout.
- Runs on `127.0.0.1:8600` via `uvicorn.run(...)` in `if __name__ == "__main__"`.

### `scripts/detect_plate.py` (slimmed)

Becomes a thin CLI wrapper: loads models itself (CPU or GPU, same
autodetect), calls `plate_pipeline.detect_and_recognize(...)`, prints the
JSON result — kept only for manual/standalone testing from the command line,
no longer invoked by PHP.

### `app/Http/Controllers/PlateOcrController.php`

`processPlateImage($absolutePath)` rewritten:

1. `ensureBackendRunning()`:
   - `GET http://127.0.0.1:8600/health` with a ~1s timeout via Laravel's
     `Http` facade.
   - On failure, spawn `py scripts/backend_server.py` detached (Windows:
     `proc_open` with `start /B`, redirecting stdout/stderr to
     `storage/logs/gpu_backend.log`), then poll `/health` every ~1s for up to
     ~90s (covers first-run model load / GPU init). If it never comes up,
     `processPlateImage` returns the existing failure shape
     (`success: false`) and the existing placeholder-plate fallback path in
     `detect()`/`detectExit()` kicks in unchanged.
   - If two requests race and both try to spawn, the loser's uvicorn process
     fails to bind port 8600 and exits; the winner's health check still
     succeeds for both callers. No extra locking needed.
2. `POST http://127.0.0.1:8600/detect` with `{"image_path": $absolutePath}`,
   ~30s timeout (steady-state single inference call).
3. Parse JSON response (same shape as before consumers expect — `success`,
   `plate_text`, `confidence`, `cropped_path`, `detection_confidence`,
   `ocr_confidence`, `ocr_engine` — plus new `telemetry`).
4. Log to `php://stderr` in the existing
   `>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<` block style, adding a
   line: `YOLO: {yolo_ms}ms | Paddle: {paddle_ms}ms | Total: {total_ms}ms`.
5. Everything downstream of `processPlateImage()` (entry/exit/stream
   handlers, DB writes, QR generation) is unchanged — same result shape in,
   same behavior out.

## Environment changes required

Both currently CPU-only, and the installed torch build additionally fails
to load (`shm.dll`) — needs fixing either way:

```
py -m pip install torch==2.9.1+cu126 --index-url https://download.pytorch.org/whl/cu126 --force-reinstall
py -m pip install paddlepaddle-gpu==3.3.1 -i https://www.paddlepaddle.org.cn/packages/stable/cu126/ --force-reinstall
```

Verified available on this machine (pip index check) before writing this
spec. ~2-3GB combined download.

## Error handling

- GPU absent/broken at backend startup → falls back to CPU automatically,
  logs it once, telemetry still reported (just slower).
- Backend can't be started or never becomes healthy → `processPlateImage`
  returns `success: false`; existing placeholder-plate fallback in
  `detect()`/`detectExit()`/`detectFromStream()` is unchanged.
- PaddleOCR/YOLO throwing on a given image → caught in
  `plate_pipeline.detect_and_recognize`, returned as
  `{"success": false, "error": ...}`, same as current script's exception
  handling.

## Testing

Manual (no automated test suite exists for this pipeline today):

1. Start backend manually (`py scripts/backend_server.py`), confirm
   `device: cuda` in startup log and `/health`.
2. `curl -X POST http://127.0.0.1:8600/detect -d '{"image_path": "<abs path to sample plate image>"}'`
   — confirm same plate/confidence output as current CPU script on a known
   sample, plus telemetry fields present and non-zero.
3. Exercise the real flow through the Laravel entry form end-to-end,
   confirm: image processed, DB entry created, telemetry line appears in
   both the Python backend terminal and PHP's stderr log.
4. Stop the backend, hit the Laravel entry form again with backend not
   running — confirm auto-spawn happens and the request still succeeds
   (allow up to ~90s for first-run model load).
5. Kill backend process, then simulate it being permanently unavailable
   (e.g. block port) — confirm existing placeholder-plate fallback still
   fires and the app doesn't hard-error.
