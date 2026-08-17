<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\PlateEntry;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PlateOcrController extends Controller
{
    /**
     * Run YOLOv8 and PaddleOCR/EasyOCR detection on a given image file path
     *
     * @param string $absolutePath Absolute path to the image
     * @return array
     */
    private function processPlateImage($absolutePath)
    {
        try {
            $scriptPath = base_path('scripts/detect_plate.py');
            
            // In Windows, run using 'py'
            $command = "py " . escapeshellarg($scriptPath) . " " . escapeshellarg($absolutePath);
            
            $startTime = microtime(true);
            $output = shell_exec($command);
            $endTime = microtime(true);
            
            $duration = round($endTime - $startTime, 2);
            
            if (empty($output)) {
                $errorMsg = 'Python script returned empty output.';
                file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
                file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
                file_put_contents('php://stderr', "Time Elapsed: {$duration} seconds\n");
                file_put_contents('php://stderr', "Detection Result: FAILED - {$errorMsg}\n");
                file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");
                return [
                    'success' => false,
                    'error' => $errorMsg
                ];
            }
            
            $result = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errorMsg = 'Failed to parse JSON output: ' . $output;
                file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
                file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
                file_put_contents('php://stderr', "Time Elapsed: {$duration} seconds\n");
                file_put_contents('php://stderr', "Detection Result: FAILED - {$errorMsg}\n");
                file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");
                return [
                    'success' => false,
                    'error' => $errorMsg
                ];
            }
            
            $resultText = "Unknown";
            if ($result && isset($result['success']) && $result['success']) {
                $resultText = "Plate: " . $result['plate_text'] . " (Conf: " . $result['confidence'] . "%, Engine: " . ($result['ocr_engine'] ?? 'paddleocr') . ")";
            } else {
                $resultText = "FAILED - " . ($result['error'] ?? 'Unknown Error');
            }
            
            file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
            file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
            file_put_contents('php://stderr', "Time Elapsed: {$duration} seconds\n");
            file_put_contents('php://stderr', "Detection Result: {$resultText}\n");
            file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");
            
            return $result;
        } catch (\Exception $e) {
            file_put_contents('php://stderr', "\n\n>>> [YOLOv8 & OCR Pipeline Execution Stats] <<<\n");
            file_put_contents('php://stderr', "Image Path: {$absolutePath}\n");
            file_put_contents('php://stderr', "Detection Exception: " . $e->getMessage() . "\n");
            file_put_contents('php://stderr', ">>> ----------------------------------- <<<\n\n");
            return [
                'success' => false,
                'error' => 'Exception during plate detection: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Display the license plate detection interface (ENTRY camera)
     */
    public function index()
    {
        return view("plate-ocr.index");
    }

    /**
     * Display the exit camera interface (EXIT camera)
     */
    public function exitIndex()
    {
        return view("plate-ocr.exit");
    }

    /**
     * Process captured image from EXIT camera and mark vehicle as exited
     */
    public function detectExit(Request $request)
    {
        $request->validate([
            "image" => "required|image|mimes:jpeg,png,jpg,gif,webp|max:5120",
        ]);

        try {
            $file = $request->file("image");
            $filename =
                time() .
                "_exit_plate_" .
                uniqid() .
                "." .
                $file->getClientOriginalExtension();
            $path = $file->storeAs("plates", $filename, "public");

            $absolutePath = storage_path("app/public/" . $path);

            // Run YOLOv8 + PaddleOCR detection
            $ocrResult = $this->processPlateImage($absolutePath);

            $plateNumber = "UNKNOWN";
            $confidence = 95.00;
            $ocrEngine = "none";

            if ($ocrResult && isset($ocrResult['success']) && $ocrResult['success']) {
                $plateNumber = $ocrResult['plate_text'];
                $confidence = $ocrResult['confidence'];
                $ocrEngine = $ocrResult['ocr_engine'] ?? 'paddleocr';
            } else {
                logger()->warning("YOLO exit detection failed: " . ($ocrResult['error'] ?? 'Unknown error'));
                $plateNumber = $this->detectPlateNumber($file);
                $confidence = 30.00;
                
                // Copy original as fallback cropped
                $base = pathinfo($absolutePath, PATHINFO_FILENAME);
                $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
                $fallbackCroppedAbsolute = dirname($absolutePath) . '/' . $base . '_cropped.' . $ext;
                if (!file_exists($fallbackCroppedAbsolute)) {
                    copy($absolutePath, $fallbackCroppedAbsolute);
                }
            }

            $exitTimestamp = now();

            // Find the most recent 'entered' record for this plate
            $entry = PlateEntry::where("plate_number", $plateNumber)
                ->where("status", "entered")
                ->latest("entry_time")
                ->first();

            $isMatchFound = false;

            if ($entry) {
                // Calculate duration
                $durationMinutes = $entry->entry_time->diffInMinutes($exitTimestamp);

                // Calculate Parking Fee
                $hours = max(1, (int)ceil($durationMinutes / 60));
                if ($hours <= 3) {
                    $totalFeeAmount = 5.00;
                } else {
                    $totalFeeAmount = 5.00 + (($hours - 3) * 2.00);
                }

                // Mark the existing entry as exited
                $entry->update([
                    "status" => "exited",
                    "exit_time" => $exitTimestamp,
                    "exit_confidence" => $confidence, // Store as percentage
                    "exit_image_path" => $path,
                    "gate_exit" => "Gate 2",
                    "duration_minutes" => $durationMinutes,
                    "parking_fee" => $totalFeeAmount,
                    "payment_status" => "unpaid",
                    "remarks" => trim(($entry->remarks ?? "") . " | Exited using YOLOv8 & " . ucfirst($ocrEngine))
                ]);

                $isMatchFound = true;
                $entryTime = $entry->entry_time;
                $duration = $entry->entry_time->diff($exitTimestamp);
                $minutes = $durationMinutes;
            } else {
                // No matching entry found — log as exit-only detection
                $totalFeeAmount = 5.00; // Base fee for unknown duration
                $minutes = 60; // Default assumption

                $entry = PlateEntry::create([
                    "plate_number" => $plateNumber,
                    "entry_confidence" => 0.00,
                    "exit_confidence" => $confidence,
                    "entry_time" => $exitTimestamp,
                    "exit_time" => $exitTimestamp,
                    "exit_image_path" => $path,
                    "gate_entry" => "Unknown",
                    "gate_exit" => "Gate 2",
                    "duration_minutes" => 0,
                    "parking_fee" => $totalFeeAmount,
                    "payment_status" => "unpaid",
                    "status" => "exited",
                    "remarks" => "Exit only: Detected using YOLOv8 & " . ucfirst($ocrEngine)
                ]);
                $entryTime = null;
                $duration = null;
            }

            $formattedFee = "$" . number_format($totalFeeAmount, 2);

            // Generate Payment Verification QR Code
            $paymentPayload = json_encode([
                "type" => "PARKING_PAYMENT",
                "facility" => "Autotrace Parking",
                "plate" => $plateNumber,
                "time_in" => $entryTime ? $entryTime->format("Y-m-d H:i:s") : "N/A",
                "time_out" => $exitTimestamp->format("Y-m-d H:i:s"),
                "duration_minutes" => $minutes,
                "amount" => $totalFeeAmount,
                "currency" => "USD",
                "payment_mode" => "DIGITAL_QR_PAYMENT",
            ]);

            $paymentQrImage = QrCode::format("svg")
                ->size(300)
                ->encoding("UTF-8")
                ->generate($paymentPayload);

            $paymentQrCode = base64_encode($paymentQrImage);

            return view("plate-ocr.exit-result", [
                "imagePath" => asset("storage/" . $path),
                "plateNumber" => $plateNumber,
                "exitTimestamp" => $exitTimestamp,
                "entryTime" => $entryTime,
                "duration" => $duration,
                "isMatchFound" => $isMatchFound,
                "entry" => $entry,
                "totalFeeAmount" => $totalFeeAmount,
                "formattedFee" => $formattedFee,
                "paymentQrCode" => $paymentQrCode,
                "paymentPayload" => $paymentPayload,
                "confidence" => $confidence,
                "ocrEngine" => $ocrEngine,
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with("error", "Error processing exit plate: " . $e->getMessage());
        }
    }

    /**
     * Process captured image and detect license plate
     */
    public function detect(Request $request)
    {
        $request->validate([
            "image" => "required|image|mimes:jpeg,png,jpg,gif,webp|max:5120",
        ]);

        try {
            $file = $request->file("image");
            $filename =
                time() .
                "_plate_" .
                uniqid() .
                "." .
                $file->getClientOriginalExtension();
            $path = $file->storeAs("plates", $filename, "public");

            $absolutePath = storage_path("app/public/" . $path);

            // Run YOLOv8 + PaddleOCR detection
            $ocrResult = $this->processPlateImage($absolutePath);

            $plateNumber = "UNKNOWN";
            $confidence = 95.00;
            $ocrEngine = "none";

            if ($ocrResult && isset($ocrResult['success']) && $ocrResult['success']) {
                $plateNumber = $ocrResult['plate_text'];
                $confidence = $ocrResult['confidence'];
                $ocrEngine = $ocrResult['ocr_engine'] ?? 'paddleocr';
            } else {
                logger()->warning("YOLO detection failed: " . ($ocrResult['error'] ?? 'Unknown error'));
                $plateNumber = $this->detectPlateNumber($file);
                $confidence = 30.00;
                
                // Copy original as fallback cropped
                $base = pathinfo($absolutePath, PATHINFO_FILENAME);
                $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
                $fallbackCroppedAbsolute = dirname($absolutePath) . '/' . $base . '_cropped.' . $ext;
                if (!file_exists($fallbackCroppedAbsolute)) {
                    copy($absolutePath, $fallbackCroppedAbsolute);
                }
            }

            $timestamp = now();

            // Persist entry so admins can view it on the dashboard/history
            $entry = PlateEntry::create([
                "plate_number" => $plateNumber,
                "entry_confidence" => $confidence,
                "entry_time" => $timestamp,
                "entry_image_path" => $path,
                "gate_entry" => "Gate 1",
                "vehicle_type" => "car",
                "status" => "entered",
                "remarks" => "Detected using YOLOv8 & " . ucfirst($ocrEngine)
            ]);

            // Generate QR code data
            $qrData = $this->generateQrData($plateNumber, $timestamp);

            // Generate QR code image
            $qrCode = QrCode::format("svg")
                ->size(300)
                ->encoding("UTF-8")
                ->generate($qrData);

            $qrBase64 = base64_encode($qrCode);

            return view("plate-ocr.result", [
                "imagePath" => asset("storage/" . $path),
                "plateNumber" => $plateNumber,
                "timestamp" => $timestamp,
                "qrCode" => $qrBase64,
                "qrData" => $qrData,
                "confidence" => $confidence,
                "ocrEngine" => $ocrEngine,
                "entry" => $entry,
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with("error", "Error processing plate: " . $e->getMessage());
        }
    }

    /**
     * Detect license plate number (placeholder for real OCR)
     */
    private function detectPlateNumber($file)
    {
        // TODO: Replace with actual license plate detection library
        // Options:
        // - OpenALPR (Automatic License Plate Recognition)
        // - OpenCV with Python + Laravel integration
        // - Google Cloud Vision API
        // - Azure Computer Vision
        // - Custom ML model

        // For now, return placeholder
        // Format: ABC-1234 (Philippine format)
        $placeholders = [
            "ABC-1234",
            "XYZ-5678",
            "PQR-9012",
            "LMN-3456",
            "UVW-7890",
        ];

        return $placeholders[array_rand($placeholders)];
    }

    /**
     * Generate QR code data with plate and timestamp
     */
    private function generateQrData($plateNumber, $timestamp)
    {
        return json_encode([
            "plate" => $plateNumber,
            "timestamp" => $timestamp->format("Y-m-d H:i:s"),
            "datetime_unix" => $timestamp->timestamp,
            "checkpoint" => "Main Gate",
            "location" => "Parking Entrance",
        ]);
    }

    /**
     * Print POS ticket for a specific entry
     */
    public function printTicket($id)
    {
        $entry = PlateEntry::findOrFail($id);
        
        if ($entry->status === 'exited') {
            // Generate Payment QR for exited
            $paymentPayload = json_encode([
                "type" => "PARKING_PAYMENT",
                "facility" => "Autotrace Parking",
                "plate" => $entry->plate_number,
                "time_in" => $entry->entry_time ? $entry->entry_time->format("Y-m-d H:i:s") : "N/A",
                "time_out" => $entry->exit_time ? $entry->exit_time->format("Y-m-d H:i:s") : "N/A",
                "duration_minutes" => $entry->duration_minutes,
                "amount" => $entry->parking_fee,
                "currency" => "USD",
                "payment_mode" => "DIGITAL_QR_PAYMENT",
            ]);
            $qrImage = QrCode::format("svg")->size(300)->encoding("UTF-8")->generate($paymentPayload);
            $qrCode = base64_encode($qrImage);
            $isExit = true;
        } else {
            // Generate Entry QR
            $qrData = $this->generateQrData($entry->plate_number, $entry->entry_time ?: now());
            $qrImage = QrCode::format("svg")->size(300)->encoding("UTF-8")->generate($qrData);
            $qrCode = base64_encode($qrImage);
            $isExit = false;
        }
        
        $duration = null;
        if ($isExit && $entry->entry_time && $entry->exit_time) {
            $duration = $entry->entry_time->diff($entry->exit_time);
        }

        return view('plate-ocr.print-ticket', compact('entry', 'qrCode', 'isExit', 'duration'));
    }

    /**
     * Download QR code for printing
     */
    public function downloadQr(Request $request)
    {
        $plateNumber = $request->input("plate", "PLATE");
        $timestamp = $request->input("timestamp", now()->format("Y-m-d H:i:s"));
        $qrData = json_encode([
            "plate" => $plateNumber,
            "timestamp" => $timestamp,
        ]);

        $qrCode = QrCode::format("svg")
            ->size(500)
            ->encoding("UTF-8")
            ->generate($qrData);

        return response($qrCode)
            ->header("Content-Type", "image/svg+xml")
            ->header("Content-Disposition", 'attachment; filename="gate-pass.svg"');
    }

    /**
     * Dashboard (combined scan + history)
     */
    public function dashboard(Request $request)
    {
        $query = PlateEntry::query();

        if ($request->filled("plate")) {
            $query->where(
                "plate_number",
                "like",
                "%" . $request->input("plate") . "%",
            );
        }

        if ($request->filled("date_filter")) {
            switch ($request->input("date_filter")) {
                case "today":
                    $query->whereDate("entry_time", now()->toDateString());
                    break;
                case "week":
                    $query->whereBetween("entry_time", [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]);
                    break;
                case "month":
                    $query->whereMonth("entry_time", now()->month);
                    break;
            }
        }

        $entries = $query->latest("entry_time")->paginate(15);

        $total = PlateEntry::count();
        $today = PlateEntry::whereDate(
            "entry_time",
            now()->toDateString(),
        )->count();
        $week = PlateEntry::whereBetween("entry_time", [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();

        // Calculate actual OCR accuracy from confidence scores
        $avgConfidence = PlateEntry::whereNotNull('entry_confidence')
            ->where('entry_confidence', '>', 0)
            ->avg('entry_confidence');
        $successRate = $avgConfidence ? round($avgConfidence, 1) : 95;

        // Exited vehicles stats
        $totalExited = PlateEntry::where("status", "exited")->count();
        $currentlyInside = PlateEntry::where("status", "entered")->count();
        $exitedToday = PlateEntry::where("status", "exited")
            ->whereDate("exit_time", now()->toDateString())
            ->count();

        // Recent exited vehicles (latest 5)
        $recentExited = PlateEntry::where("status", "exited")
            ->latest("exit_time")
            ->take(5)
            ->get();

        return view(
            "dashboard",
            compact("entries", "total", "today", "week", "successRate", "totalExited", "currentlyInside", "exitedToday", "recentExited"),
        );
    }

    /**
     * Dual Live Cameras Terminal (Gate 1 & Gate 2 side-by-side live stream)
     */
    public function dualIndex()
    {
        return view("plate-ocr.dual");
    }

    /**
     * History page with latest entered and exited vehicles
     */
    public function history(Request $request)
    {
        $query = PlateEntry::query();

        if ($request->filled("plate")) {
            $query->where(
                "plate_number",
                "like",
                "%" . $request->input("plate") . "%",
            );
        }

        if ($request->filled("status_filter") && $request->input("status_filter") !== "") {
            $query->where("status", $request->input("status_filter"));
        }

        if ($request->filled("date_filter")) {
            switch ($request->input("date_filter")) {
                case "today":
                    $query->whereDate("entry_time", now()->toDateString());
                    break;
                case "week":
                    $query->whereBetween("entry_time", [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]);
                    break;
                case "month":
                    $query->whereMonth("entry_time", now()->month);
                    break;
            }
        }

        $entries = $query->latest("entry_time")->paginate(15);

        $total = PlateEntry::count();
        $today = PlateEntry::whereDate(
            "entry_time",
            now()->toDateString(),
        )->count();
        $week = PlateEntry::whereBetween("entry_time", [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();
        $successRate = $total > 0 ? 100 : 0;

        // Latest entered vehicles (top 10)
        $latestEntered = PlateEntry::where("status", "entered")
            ->latest("entry_time")
            ->take(10)
            ->get();

        // Latest exited vehicles (top 10)
        $latestExited = PlateEntry::where("status", "exited")
            ->latest("exit_time")
            ->take(10)
            ->get();

        $totalExited = PlateEntry::where("status", "exited")->count();
        $currentlyInside = PlateEntry::where("status", "entered")->count();

        return view(
            "plate-ocr.history",
            compact("entries", "total", "today", "week", "successRate", "latestEntered", "latestExited", "totalExited", "currentlyInside"),
        );
    }

    /**
     * Mark a vehicle as exited
     */
    public function exitVehicle(Request $request, $id)
    {
        $entry = PlateEntry::findOrFail($id);

        if ($entry->status === "exited") {
            return redirect()->back()->with("error", "This vehicle has already exited.");
        }

        $exitTime = now();
        $durationMinutes = $entry->entry_time ? $entry->entry_time->diffInMinutes($exitTime) : 0;

        // Calculate parking fee
        $hours = max(1, (int)ceil($durationMinutes / 60));
        if ($hours <= 3) {
            $parkingFee = 5.00;
        } else {
            $parkingFee = 5.00 + (($hours - 3) * 2.00);
        }

        $entry->update([
            "status" => "exited",
            "exit_time" => $exitTime,
            "gate_exit" => "Gate 2",
            "duration_minutes" => $durationMinutes,
            "parking_fee" => $parkingFee,
        ]);

        return redirect()->back()->with("success", "Vehicle {$entry->plate_number} marked as exited.");
    }

    /**
     * API endpoint for real-time camera detection
     */
    public function detectFromStream(Request $request)
    {
        $request->validate([
            "frame" => "required|string",
        ]);

        try {
            // Decode base64 image from camera stream
            $imageData = base64_decode(
                preg_replace(
                    "#^data:image/\w+;base64,#i",
                    "",
                    $request->input("frame"),
                ),
            );
            $tempPath = storage_path("temp/plate-" . time() . ".jpg");

            // Ensure temp directory exists
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $imageData);

            // Run YOLOv8 + PaddleOCR detection
            $ocrResult = $this->processPlateImage($tempPath);

            $plateNumber = "UNKNOWN";
            $confidence = 95.00;
            $ocrEngine = "none";
            $croppedPath = null;

            if ($ocrResult && isset($ocrResult['success']) && $ocrResult['success']) {
                $plateNumber = $ocrResult['plate_text'];
                $confidence = $ocrResult['confidence'];
                $ocrEngine = $ocrResult['ocr_engine'] ?? 'paddleocr';
                $croppedPath = $ocrResult['cropped_path'] ?? null;
            } else {
                $plateNumber = $this->detectPlateNumber(null);
                $confidence = 30.00;
            }

            $timestamp = now();

            // Store permanently as plate entry (Gate 1) if not duplicated within 10 seconds
            $recent = PlateEntry::where('plate_number', $plateNumber)
                ->where('entry_time', '>=', now()->subSeconds(10))
                ->first();

            if (!$recent) {
                // Save original to public plates
                $filename = time() . "_stream_" . uniqid() . ".jpg";
                $publicPath = "plates/" . $filename;
                $publicFullPath = storage_path("app/public/" . $publicPath);
                
                copy($tempPath, $publicFullPath);
                
                // Copy cropped if exists
                if ($croppedPath) {
                    $croppedFilename = time() . "_stream_" . uniqid() . "_cropped.jpg";
                    $croppedPublicPath = "plates/" . $croppedFilename;
                    $croppedPublicFullPath = storage_path("app/public/" . $croppedPublicPath);
                    copy(base_path($croppedPath), $croppedPublicFullPath);
                } else {
                    // Save full as cropped fallback
                    $croppedFilename = time() . "_stream_" . uniqid() . "_cropped.jpg";
                    $croppedPublicPath = "plates/" . $croppedFilename;
                    $croppedPublicFullPath = storage_path("app/public/" . $croppedPublicPath);
                    copy($tempPath, $croppedPublicFullPath);
                }

                PlateEntry::create([
                    "plate_number" => $plateNumber,
                    "entry_confidence" => $confidence,
                    "entry_time" => $timestamp,
                    "entry_image_path" => $publicPath,
                    "gate_entry" => "Gate 1",
                    "vehicle_type" => "car",
                    "status" => "entered",
                    "remarks" => "Live Stream: Detected using YOLOv8 & " . ucfirst($ocrEngine)
                ]);
            }

            // Cleanup temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return response()->json([
                "success" => true,
                "plate" => $plateNumber,
                "timestamp" => $timestamp->toIso8601String(),
                "confidence" => $confidence / 100.0,
                "ocr_engine" => $ocrEngine
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                ],
                400,
            );
        }
    }
}
