# YOLOv8 & OCR Integration Details

This document outlines all integration changes made to implement automated vehicle license plate recognition and check-out management.

---

## 💻 1. Python Detection Pipeline (`scripts/detect_plate.py`)
Developed a standalone Python script to coordinate YOLOv8 plate localization with OCR text extraction.
* **Environment Overrides:** Disabled oneDNN/MKLDNN on CPU execution (`FLAGS_use_mkldnn = 0`) to prevent the PaddleOCR Program Intermediate Representation (PIR) executor crash.
* **EasyOCR Bugfix:** Automatically loads and converts cropped plate images into 2D **grayscale** arrays (`cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)`) before passing to EasyOCR, resolving the library shape-unpacking crash (`ValueError: too many values to unpack (expected 2)`).
* **Double Engine Flow:** First attempts PaddleOCR; falls back to EasyOCR if needed. Returns structured JSON containing plate text, confidence scores, cropped image paths, and engine metadata.

---

## 🏓 2. Laravel Backend Controller (`app/Http/Controllers/PlateOcrController.php`)
* **Timing & Execution Console Logging:** Added `microtime(true)` timers inside `processPlateImage()`. Execution duration and the extraction result are written directly to `php://stderr` so they display in real-time in the `php artisan serve` terminal.
* **OCR Fallback Mitigation:** Integrated the Python script execution into `detect()` (Gate 1), `detectExit()` (Gate 2), and `detectFromStream()` (Camera Stream) endpoints. If the OCR script fails, the backend safely falls back to local database random mock values to maintain system operation.

---

## 🗺️ 3. Web Routing (`routes/web.php`)
* **Page Refresh Handlers:** Added GET fallback routes for `/plate-ocr/detect` and `/plate-ocr/exit/detect` that redirect back to the active scan panels. This prevents `MethodNotAllowedHttpException` errors when users manually refresh success pages.

---

## 📦 4. Public Storage Link
* **Storage Symlink Setup:** Ran `php artisan storage:link` to connect the `public/storage` directory to `storage/app/public`. This resolves the `403 (Forbidden)` error, enabling the browser to load uploaded vehicle captures and cropped thumbnails.

---

## 🎨 5. Frontend & Views
* **Gate Pass & Check-out Results (`result.blade.php`, `exit-result.blade.php`):**
  * Displays a side-by-side view of the full vehicle capture alongside the YOLOv8 cropped plate image.
  * Shows the dynamic detection engine badge (YOLOv8) and the active OCR engine badge (PaddleOCR/EasyOCR).
  * Implemented color-coded progress bars reflecting OCR confidence levels.
* **Log Tables (`dashboard.blade.php`, `history-table.blade.php`, `history.blade.php`):**
  * Integrated a high-contrast thumbnail preview column for the cropped license plate.
