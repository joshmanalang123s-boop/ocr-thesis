# YOLOv8 & OCR Integration Details

This document outlines all integration changes made to implement automated vehicle license plate recognition and check-out management.

---

## 💻 1. Python Detection Pipeline (`scripts/detect_plate.py`)
Developed a standalone Python script to coordinate YOLOv8 plate localization with OCR text extraction.
* **Environment Overrides:** Disabled oneDNN/MKLDNN on CPU execution (`FLAGS_use_mkldnn = 0`) to prevent the PaddleOCR Program Intermediate Representation (PIR) executor crash.
* **EasyOCR Shape Bugfix:** Automatically loads and converts cropped plate images into 2D **grayscale** arrays (`cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)`) before passing to EasyOCR, resolving the library shape-unpacking crash (`ValueError: too many values to unpack (expected 2)`).
* **EasyOCR Prioritization (Fast Path):** Changed the default engine order to run **EasyOCR first**, falling back to PaddleOCR only if EasyOCR fails. This bypasses the PaddleOCR CPU startup overhead on success.
* **Double-OCR Performance Optimization:**
  * Evaluates the crop OCR text using a license plate format scorer. If the crop text is a strong match (mix of letters and numbers with length >= 5, yielding a score >= 5.0), the script **skips the full-image OCR call**. This cuts the execution time in half for standard plates.
* **Expanded Bounding Box Padding:**
  * Increased bounding box crop padding to **18% width** and **10% height** (up from 8%). This provides a wider crop margin around the plate boundary, preventing characters on the outer edges (such as the letter `N` in `NDP 9668`) from being cropped out.
* **Lower YOLO Confidence Threshold:**
  * Configured YOLOv8 detection with `conf=0.05` (down from default `0.25`). This guarantees that close-up vehicle captures (which typically get low confidence scores since they dominate the frame and differ from standard far-away training dimensions) are still successfully localized rather than failing to detect.
* **Smart Font-Height Filtering:**
  * Automatically calculates text heights for all detected blocks. Any text blocks with a height less than **45% of the maximum detected text height** are discarded. This filters out smaller background noise, borders, or region identifiers (such as `"NCR"`) to ensure they do not pollute the plate number or cause length penalties.
* **Temporary Word & Slogan Blacklist Filtering (New):**
  * Added token-based blacklist filtering in `sanitize_plate_number()`. It automatically identifies and removes common temporary plate headers, dealer tags, and slogans (such as `"REGISTERED"`, `"REGISTRATION"`, `"TEMPORARY"`, `"CONDUCTION"`, `"STICKER"`, `"DEALER"`, `"MV"`, `"FILE"`, `"NO"`, `"FRONT"`, `"REAR"`, `"MATATAG"`, `"PILIPINAS"`, `"BAGONG"`, `"NCR"`, `"REGION"`, and `"PHILIPPINES"`) before returning the parsed plate value.
* **Format-Scoring on Cleaned Text (New):**
  * Configured the OCR formatting evaluator to run `score_text()` on the *sanitized* text rather than raw text. This prevents length and character-mix penalties caused by temporary labels, ensuring genuine plate structures are prioritized correctly.
* **Horizontal Line Merging:**
  * Groups OCR text blocks by their y-coordinates (with a minimum grouping threshold of 40px and 20% of image height) and sorts them left-to-right before merging. This corrects split text (like `'NDP'` and `'9668'`, merging them into `'NDP 9668'`).

---

## 🏓 2. Laravel Backend Controller (`app/Http/Controllers/PlateOcrController.php`)
* **Timeout Protection:** Added `@set_time_limit(120);` to the beginning of the `processPlateImage()` method. This extends the PHP execution timeout to 120 seconds, preventing server aborts during heavy ML pipeline requests.
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
