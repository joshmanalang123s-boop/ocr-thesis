<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Schema\Blueprint;
use App\Models\PlateEntry;
use App\Models\ParkingSession;
use App\Models\ParkingRate;
use App\Models\PlateEntryAudit;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PlateOcrController extends Controller
{
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

        $pythonPath = 'C:\\Users\\Josh\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

        $process = proc_open(
            [$pythonPath, $scriptPath],
            $descriptorspec,
            $pipes,
            base_path(),
            null,
            ['bypass_shell' => true]
        );

        if (is_resource($process)) {
            fclose($pipes[0]);
        }

        // First boot loads YOLO onto the GPU + OCR, check health with short 5s deadline
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            usleep(300000);
            try {
                $response = Http::timeout(1)->get($healthUrl);
                if ($response->successful()) {
                    return true;
                }
            } catch (\Exception $e) {
                // Still prostarting up, keep polling.
            }
        }

        return false;
    }

    /**
     * Run YOLOv8 detection and fast-plate-ocr recognition on a given image file path via the GPU backend.
     *
     * @param string $absolutePath Absolute path to the image
     * @return array
     */
    private function processPlateImage($absolutePath)
    {
        @set_time_limit(120);

        $startTime = microtime(true);

        try {
            // Fast path: POST directly without health-check overhead
            try {
                $response = Http::timeout(10)->post('http://127.0.0.1:8600/detect', [
                    'image_path' => $absolutePath,
                ]);
            } catch (\Exception $e) {
                // Backend might be booting or offline; spawn/wait and retry once
                if (!$this->ensureBackendRunning()) {
                    $errorMsg = 'GPU OCR backend is not available.';
                    file_put_contents('php://stderr', "\n>>> [OCR Pipeline] FAILED: {$errorMsg} <<<\n");
                    return [
                        'success' => false,
                        'error' => $errorMsg,
                    ];
                }

                $response = Http::timeout(15)->post('http://127.0.0.1:8600/detect', [
                    'image_path' => $absolutePath,
                ]);
            }

            $duration = round(microtime(true) - $startTime, 2);

            if (!$response->successful()) {
                $errorMsg = 'Backend returned HTTP ' . $response->status();
                file_put_contents('php://stderr', "\n>>> [OCR Pipeline] FAILED: {$errorMsg} ({$duration}s) <<<\n");
                return [
                    'success' => false,
                    'error' => $errorMsg,
                ];
            }

            $result = $response->json();

            $resultText = "Unknown";
            if ($result && isset($result['success']) && $result['success']) {
                $resultText = "Plate: " . $result['plate_text'] . " (Conf: " . $result['confidence'] . "%, Engine: " . ($result['ocr_engine'] ?? 'fastplateocr') . ")";
            } else {
                $resultText = "FAILED - " . ($result['error'] ?? 'Unknown Error');
            }

            $telemetry = $result['telemetry'] ?? [];
            $yoloMs = $telemetry['yolo_ms'] ?? 'n/a';
            $ocrMs = $telemetry['ocr_ms'] ?? 'n/a';
            $totalMs = $telemetry['total_ms'] ?? 'n/a';

            file_put_contents('php://stderr', "\n>>> [OCR] {$resultText} | YOLO: {$yoloMs}ms OCR: {$ocrMs}ms Total: {$totalMs}ms ({$duration}s) <<<\n");

            return $result;
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            file_put_contents('php://stderr', "\n>>> [OCR Pipeline] Exception: " . $e->getMessage() . " ({$duration}s) <<<\n");
            return [
                'success' => false,
                'error' => 'Exception during plate detection: ' . $e->getMessage(),
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
     * Calculate parking fee based on duration in minutes and vehicle type.
     * Enforces mandatory 15-minute free parking rule (<= 15 minutes is ₱0.00).
     *
     * @param int $durationMinutes
     * @param string $vehicleType
     * @return float
     */
    public static function calculateParkingFee($durationMinutes, $vehicleType = 'car')
    {
        $breakdown = ParkingRate::getActiveRate()->calculateFee($durationMinutes, $vehicleType);
        return (float)$breakdown['fee'];
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

            // Run YOLOv8 + fast-plate-ocr detection
            $ocrResult = $this->processPlateImage($absolutePath);

            $plateNumber = "UNKNOWN";
            $confidence = 95.00;
            $ocrEngine = "none";

            if ($ocrResult && isset($ocrResult['success']) && $ocrResult['success']) {
                $plateNumber = $ocrResult['plate_text'];
                $confidence = $ocrResult['confidence'];
                $ocrEngine = $ocrResult['ocr_engine'] ?? 'fastplateocr';
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

            // Find the most recent 'entered' record for this plate using multi-tier matching
            $matchResult = $this->findMatchingEnteredVehicle($plateNumber);
            $entry = $matchResult ? $matchResult['entry'] : null;
            $isMatchFound = ($entry !== null);

            $activeRate = ParkingRate::getActiveRate();

            if ($entry) {
                // Calculate duration in minutes accurately
                $durationMinutes = $entry->entry_time ? $entry->entry_time->diffInMinutes($exitTimestamp) : 0;

                // Check for Monthly Membership Coverage
                $activeMember = \App\Models\Member::findActiveMemberByPlate($plateNumber) 
                    ?? \App\Models\Member::findActiveMemberByPlate($entry->plate_number);
                $isCoveredByMembership = ($activeMember && $activeMember->isCurrentlyActive());

                if ($isCoveredByMembership) {
                    $totalFeeAmount = 0.00;
                    $isFreeGrace = false;
                    $paymentStatus = "paid";
                    $ruleApplied = "MEMBER COVERED: {$activeMember->full_name} (Valid until " . $activeMember->end_date->format('M d, Y') . ") — ₱0.00 Fee";
                    $appliedSnapshot = [
                        'type' => 'monthly_membership',
                        'member_id' => $activeMember->id,
                        'member_name' => $activeMember->full_name,
                        'valid_until' => $activeMember->end_date->format('Y-m-d'),
                        'fee' => 0.00,
                    ];
                } else {
                    // Calculate Parking Fee with mandatory 15-minute free grace period
                    $feeBreakdown = $activeRate->calculateFee($durationMinutes, $entry->vehicle_type ?? 'car');
                    $totalFeeAmount = $feeBreakdown['fee'];
                    $isFreeGrace = $feeBreakdown['is_free'];
                    $paymentStatus = $isFreeGrace ? "waived" : "unpaid";
                    $ruleApplied = $feeBreakdown['rule_applied'];
                    $appliedSnapshot = $feeBreakdown['snapshot'];
                }

                // Mark the existing entry as exited
                $entry->update([
                    "status" => "exited",
                    "exit_time" => $exitTimestamp,
                    "exit_confidence" => $confidence,
                    "exit_image_path" => $path,
                    "gate_exit" => "Gate 2",
                    "duration_minutes" => $durationMinutes,
                    "parking_fee" => $totalFeeAmount,
                    "is_free_grace" => $isFreeGrace,
                    "is_member" => ($activeMember !== null),
                    "member_id" => $activeMember ? $activeMember->id : $entry->member_id,
                    "covered_by_membership" => $isCoveredByMembership,
                    "applied_rate_snapshot" => $appliedSnapshot,
                    "rate_id" => $activeRate->id,
                    "payment_status" => $paymentStatus,
                    "remarks" => trim(($entry->remarks ?? "") . " | Exited: YOLOv8 & " . ucfirst($ocrEngine) . " (Tier: " . $matchResult['tier'] . ") — " . $ruleApplied)
                ]);

                $isMatchFound = true;
                $plateNumber = $entry->plate_number; // Canonical entered plate number
                $entryTime = $entry->entry_time;
                $duration = $entry->entry_time ? $entry->entry_time->diff($exitTimestamp) : null;
                $minutes = $durationMinutes;
            } else {
                // No matching entry found — log as exceptional exit detection
                $totalFeeAmount = (float)$activeRate->base_rate;
                $minutes = 0;
                $isFreeGrace = false;

                $entry = PlateEntry::create([
                    "session_id" => ParkingSession::getActive()->id,
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
                    "is_free_grace" => false,
                    "applied_rate_snapshot" => [
                        'note' => 'Exceptional: Exit recorded with missing entry record',
                        'base_rate' => $totalFeeAmount,
                    ],
                    "rate_id" => $activeRate->id,
                    "payment_status" => "unpaid",
                    "status" => "exited",
                    "remarks" => "Exceptional Exit: No active entry matched. Assessed base fee ₱" . number_format($totalFeeAmount, 2)
                ]);
                $entryTime = null;
                $duration = null;
            }

            $formattedFee = $isFreeGrace ? "₱0.00 (FREE — 15m Grace)" : "₱" . number_format($totalFeeAmount, 2);

            // Generate Payment Verification QR Code
            $paymentPayload = json_encode([
                "type" => "PARKING_PAYMENT",
                "facility" => "Autotrace Parking",
                "plate" => $plateNumber,
                "time_in" => $entryTime ? $entryTime->format("Y-m-d H:i:s") : "N/A",
                "time_out" => $exitTimestamp->format("Y-m-d H:i:s"),
                "duration_minutes" => $minutes,
                "amount" => $totalFeeAmount,
                "currency" => "PHP",
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

            // Run YOLOv8 + fast-plate-ocr detection
            $ocrResult = $this->processPlateImage($absolutePath);

            $plateNumber = "UNKNOWN";
            $confidence = 95.00;
            $ocrEngine = "none";

            if ($ocrResult && isset($ocrResult['success']) && $ocrResult['success']) {
                $plateNumber = $ocrResult['plate_text'];
                $confidence = $ocrResult['confidence'];
                $ocrEngine = $ocrResult['ocr_engine'] ?? 'fastplateocr';
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

            // Duplicate active parking session check
            $cleanPlate = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plateNumber));
            if ($cleanPlate !== 'UNKNOWN') {
                $existingActive = PlateEntry::where('status', 'entered')
                    ->get()
                    ->first(function ($item) use ($cleanPlate) {
                        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $item->plate_number)) === $cleanPlate;
                    });

                if ($existingActive && !$request->boolean('allow_duplicate')) {
                    $entryTimeFormatted = $existingActive->entry_time ? $existingActive->entry_time->format('M d, Y • h:i A') : 'earlier';
                    return redirect()
                        ->back()
                        ->with("error", "Vehicle {$plateNumber} is already currently parked inside facility (Entered at {$entryTimeFormatted}). Duplicate entry prevented.")
                        ->with("duplicate_plate", $plateNumber)
                        ->with("duplicate_entry_id", $existingActive->id);
                }
            }

            // Check for Monthly Membership Recognition
            $activeMember = \App\Models\Member::findActiveMemberByPlate($plateNumber);
            $memberRecord = $activeMember ?? \App\Models\Member::findMemberRecordByPlate($plateNumber);
            $isMember = ($activeMember !== null && $activeMember->isCurrentlyActive());
            $memberId = $memberRecord ? $memberRecord->id : null;
            $coveredByMembership = $isMember;

            $remarks = "Detected using YOLOv8 & " . ucfirst($ocrEngine);
            if ($isMember) {
                $remarks .= " | MEMBER: {$activeMember->full_name} (Expires " . $activeMember->end_date->format('M d, Y') . " — ₱0 Coverage)";
            } elseif ($memberRecord && $memberRecord->isExpired()) {
                $remarks .= " | EXPIRED MEMBER: {$memberRecord->full_name} (Expired " . $memberRecord->end_date->format('M d, Y') . " — Standard Tariff)";
            } elseif ($memberRecord && $memberRecord->payment_status === 'unpaid') {
                $remarks .= " | UNPAID MEMBER: {$memberRecord->full_name} (Standard Tariff until dues paid)";
            }

            // Persist entry so admins can view it on the dashboard/history
            $entry = PlateEntry::create([
                "session_id" => ParkingSession::getActive()->id,
                "plate_number" => $plateNumber,
                "entry_confidence" => $confidence,
                "entry_time" => $timestamp,
                "entry_image_path" => $path,
                "gate_entry" => "Gate 1",
                "vehicle_type" => $memberRecord ? $memberRecord->vehicle_type : "car",
                "status" => "entered",
                "is_member" => $isMember,
                "member_id" => $memberId,
                "covered_by_membership" => $coveredByMembership,
                "remarks" => $remarks
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
                "member" => $activeMember ?? $memberRecord,
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
                "currency" => "PHP",
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
        $activeSession = ParkingSession::getActive();

        $query = PlateEntry::query()->where('session_id', $activeSession->id);

        if ($request->filled("plate")) {
            $query->where(
                "plate_number",
                "like",
                "%" . $request->input("plate") . "%",
            );
        }

        $entries = $query->latest("entry_time")->paginate(15);

        // Active session metrics
        $total = PlateEntry::where('session_id', $activeSession->id)->count();
        $sessionRevenue = (float)PlateEntry::where('session_id', $activeSession->id)
            ->where('status', 'exited')
            ->sum('parking_fee');
        $currentlyInside = PlateEntry::where('session_id', $activeSession->id)
            ->where('status', 'entered')
            ->count();
        $completedSessions = PlateEntry::where('session_id', $activeSession->id)
            ->where('status', 'exited')
            ->count();
        $freeSessionsCount = PlateEntry::where('session_id', $activeSession->id)
            ->where('status', 'exited')
            ->where(function($q) {
                $q->where('is_free_grace', true)
                  ->orWhere('parking_fee', '<=', 0)
                  ->orWhere('duration_minutes', '<=', 15);
            })->count();
        $paidSessionsCount = max(0, $completedSessions - $freeSessionsCount);

        $activeRate = ParkingRate::getActiveRate();

        // Calculate actual OCR accuracy from confidence scores in active session
        $avgConfidence = PlateEntry::where('session_id', $activeSession->id)
            ->whereNotNull('entry_confidence')
            ->where('entry_confidence', '>', 0)
            ->avg('entry_confidence');
        $successRate = $avgConfidence ? round($avgConfidence, 1) : 98.5;

        // Recent exited vehicles in active session (latest 5)
        $recentExited = PlateEntry::where('session_id', $activeSession->id)
            ->where('status', 'exited')
            ->latest('exit_time')
            ->take(5)
            ->get();

        return view(
            "dashboard",
            compact(
                "activeSession",
                "entries",
                "total",
                "sessionRevenue",
                "currentlyInside",
                "completedSessions",
                "freeSessionsCount",
                "paidSessionsCount",
                "activeRate",
                "successRate",
                "recentExited"
            ),
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
        $activeRate = ParkingRate::getActiveRate();

        return view(
            "plate-ocr.history",
            compact("entries", "total", "today", "week", "successRate", "latestEntered", "latestExited", "totalExited", "currentlyInside", "activeRate"),
        );
    }

    /**
     * Mark a vehicle as exited (Manual exit trigger)
     */
    public function exitVehicle(Request $request, $id)
    {
        $entry = PlateEntry::findOrFail($id);

        if ($entry->status === "exited") {
            return redirect()->back()->with("error", "This vehicle has already exited.");
        }

        $exitTime = now();
        $durationMinutes = $entry->entry_time ? $entry->entry_time->diffInMinutes($exitTime) : 0;

        // Check for Monthly Membership Coverage
        $activeMember = \App\Models\Member::findActiveMemberByPlate($entry->plate_number);
        $isCoveredByMembership = ($activeMember && $activeMember->isCurrentlyActive());

        if ($isCoveredByMembership) {
            $parkingFee = 0.00;
            $isFreeGrace = false;
            $paymentStatus = "paid";
            $appliedSnapshot = [
                'type' => 'monthly_membership',
                'member_id' => $activeMember->id,
                'member_name' => $activeMember->full_name,
                'valid_until' => $activeMember->end_date->format('Y-m-d'),
                'fee' => 0.00,
            ];
            $ruleApplied = "MEMBER COVERED: {$activeMember->full_name} (Expires " . $activeMember->end_date->format('M d, Y') . ") — ₱0.00 Fee";
            $feeFormatted = "₱0.00 (MEMBER PASS)";
        } else {
            // Calculate parking fee with 15-minute free grace period
            $activeRate = ParkingRate::getActiveRate();
            $feeBreakdown = $activeRate->calculateFee($durationMinutes, $entry->vehicle_type ?? 'car');
            $parkingFee = $feeBreakdown['fee'];
            $isFreeGrace = $feeBreakdown['is_free'];
            $paymentStatus = $isFreeGrace ? "waived" : "unpaid";
            $appliedSnapshot = $feeBreakdown['snapshot'];
            $ruleApplied = $feeBreakdown['rule_applied'];
            $feeFormatted = $isFreeGrace ? "₱0.00 (FREE — 15m Grace)" : "₱" . number_format($parkingFee, 2);
        }

        $entry->update([
            "status" => "exited",
            "exit_time" => $exitTime,
            "gate_exit" => "Gate 2",
            "duration_minutes" => $durationMinutes,
            "parking_fee" => $parkingFee,
            "is_free_grace" => $isFreeGrace,
            "is_member" => ($activeMember !== null),
            "member_id" => $activeMember ? $activeMember->id : $entry->member_id,
            "covered_by_membership" => $isCoveredByMembership,
            "applied_rate_snapshot" => $appliedSnapshot,
            "rate_id" => $entry->rate_id,
            "payment_status" => $paymentStatus,
            "remarks" => trim(($entry->remarks ?? "") . " | Manual Exit: " . $ruleApplied),
        ]);

        return redirect()->back()->with("success", "Vehicle {$entry->plate_number} marked as exited. Fee: {$feeFormatted}.");
    }

    /**
     * Correct an incorrectly recognized license plate number with audit logging.
     */
    public function correctPlate(Request $request, $id)
    {
        $request->validate([
            'plate_number' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Z0-9\s\-]+$/i'],
            'correction_reason' => ['nullable', 'string', 'max:255'],
        ], [
            'plate_number.required' => 'License plate number is required.',
            'plate_number.regex' => 'License plate number must only contain letters, numbers, hyphens, and spaces.',
        ]);

        $entry = PlateEntry::findOrFail($id);
        $oldPlate = $entry->plate_number;
        $newPlate = strtoupper(trim($request->input('plate_number')));
        $reason = $request->input('correction_reason') ?: 'Manual correction of OCR read';

        if ($oldPlate === $newPlate) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'No changes made. Plate number is identical.', 'plate' => $newPlate]);
            }
            return redirect()->back()->with('info', 'No changes made. Plate number is identical.');
        }

        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Re-evaluate membership for the corrected plate number
            $activeMember = \App\Models\Member::findActiveMemberByPlate($newPlate);
            $isMember = ($activeMember !== null && $activeMember->isCurrentlyActive());

            $updateData = [
                'plate_number' => $newPlate,
                'is_manually_corrected' => true,
                'corrected_by' => $user?->id,
                'corrected_at' => now(),
                'is_member' => $isMember,
                'member_id' => $activeMember?->id ?? $entry->member_id,
                'covered_by_membership' => $isMember,
                'remarks' => trim(($entry->remarks ?? '') . " | Corrected: '{$oldPlate}' -> '{$newPlate}' ({$reason})"),
            ];

            // If the vehicle was already exited and charged, waive the fee if it is a valid member!
            if ($isMember && $entry->status === 'exited' && (float)$entry->parking_fee > 0) {
                $updateData['parking_fee'] = 0.00;
                $updateData['payment_status'] = 'paid';
                $updateData['remarks'] .= " | Fee waived: Member {$activeMember->full_name} validated upon correction.";
            }

            $entry->update($updateData);

            PlateEntryAudit::create([
                'plate_entry_id' => $entry->id,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Staff',
                'action' => 'CORRECT_PLATE',
                'field_changed' => 'plate_number',
                'old_value' => $oldPlate,
                'new_value' => $newPlate,
                'reason' => $reason . ($isMember ? " (Matched Member: {$activeMember->full_name})" : ""),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            $msg = "Plate number successfully updated from '{$oldPlate}' to '{$newPlate}'.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'old_plate' => $oldPlate,
                    'new_plate' => $newPlate,
                ]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Failed to correct plate: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to correct plate: ' . $e->getMessage());
        }
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

            // Run YOLOv8 + fast-plate-ocr detection
            $ocrResult = $this->processPlateImage($tempPath);

            $plateNumber = "UNKNOWN";
            $confidence = 95.00;
            $ocrEngine = "none";
            $croppedPath = null;

            if ($ocrResult && isset($ocrResult['success']) && $ocrResult['success']) {
                $plateNumber = $ocrResult['plate_text'];
                $confidence = $ocrResult['confidence'];
                $ocrEngine = $ocrResult['ocr_engine'] ?? 'fastplateocr';
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
                    "session_id" => ParkingSession::getActive()->id,
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

    /**
     * Unified auto-detect endpoint for automated camera detection.
     *
     * Accepts a base64 frame from any camera, runs YOLO + OCR,
     * and automatically creates entry records or closes exit sessions.
     *
     * @param Request $request  JSON: { frame, camera_id, camera_type }
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoDetect(Request $request)
    {
        $request->validate([
            "frame" => "required|string",
            "camera_id" => "required|string|max:100",
            "camera_type" => "required|in:entry,exit",
        ]);

        $cameraId = $request->input("camera_id");
        $cameraType = $request->input("camera_type");
        $minConfidence = 20.0; // accept valid reads down to 20%

        try {
            // Decode base64 frame
            $imageData = base64_decode(
                preg_replace(
                    "#^data:image/\w+;base64,#i",
                    "",
                    $request->input("frame"),
                ),
            );

            if (!$imageData || strlen($imageData) < 1000) {
                return response()->json([
                    "success" => false,
                    "no_plate" => true,
                    "message" => "Frame too small or invalid",
                ]);
            }

            // Save frame to temp
            $tempDir = storage_path("temp");
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempPath = $tempDir . "/auto_" . $cameraId . "_" . time() . "_" . uniqid() . ".jpg";
            file_put_contents($tempPath, $imageData);

            // Run YOLO + OCR pipeline
            $ocrResult = $this->processPlateImage($tempPath);

            // Check if detection was successful
            if (!$ocrResult || !isset($ocrResult['success']) || !$ocrResult['success']) {
                // Cleanup temp
                if (file_exists($tempPath)) { unlink($tempPath); }

                return response()->json([
                    "success" => false,
                    "no_plate" => true,
                    "message" => "No license plate detected in frame",
                ]);
            }

            $plateNumber = $ocrResult['plate_text'];
            $confidence = $ocrResult['confidence'];
            $ocrEngine = $ocrResult['ocr_engine'] ?? 'fastplateocr';
            $croppedPath = $ocrResult['cropped_path'] ?? null;

            // Reject low-confidence detections
            if ($confidence < $minConfidence || $plateNumber === "UNKNOWN" || strlen($plateNumber) < 3) {
                if (file_exists($tempPath)) { unlink($tempPath); }

                return response()->json([
                    "success" => false,
                    "no_plate" => true,
                    "message" => "Detection confidence too low ({$confidence}%)",
                ]);
            }

            // Derive gate name from camera_id (e.g. "gate1-entry" → "Gate 1")
            $gateNumber = "Gate 1";
            if (preg_match('/gate(\d+)/i', $cameraId, $m)) {
                $gateNumber = "Gate " . $m[1];
            }

            $timestamp = now();

            // ─── ENTRY CAMERA ───────────────────────────────────────────
            if ($cameraType === "entry") {
                // Deduplication: skip if same plate entered within 60 seconds
                $recent = PlateEntry::where('plate_number', $plateNumber)
                    ->where('status', 'entered')
                    ->where('entry_time', '>=', now()->subSeconds(60))
                    ->first();

                if ($recent) {
                    if (file_exists($tempPath)) { unlink($tempPath); }

                    return response()->json([
                        "success" => true,
                        "duplicate" => true,
                        "plate" => $plateNumber,
                        "message" => "Vehicle {$plateNumber} already recorded (dedup)",
                        "entry_id" => $recent->id,
                    ]);
                }

                // Duplicate active parking session check
                $cleanRead = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plateNumber));
                if ($cleanRead !== 'UNKNOWN') {
                    $existingActive = PlateEntry::where('status', 'entered')
                        ->get()
                        ->first(function ($item) use ($cleanRead) {
                            return strtoupper(preg_replace('/[^A-Z0-9]/', '', $item->plate_number)) === $cleanRead;
                        });

                    if ($existingActive && !$request->boolean('allow_duplicate')) {
                        if (file_exists($tempPath)) { unlink($tempPath); }
                        $enteredAt = $existingActive->entry_time ? $existingActive->entry_time->format('M d, Y • h:i A') : 'earlier';
                        return response()->json([
                            "success" => false,
                            "duplicate_active" => true,
                            "plate" => $plateNumber,
                            "existing_id" => $existingActive->id,
                            "entry_time" => $enteredAt,
                            "message" => "Vehicle {$plateNumber} is already currently parked inside facility (Entered {$enteredAt}). Duplicate entry prevented.",
                        ]);
                    }
                }

                // Save image permanently
                $baseName = time() . "_auto_entry_" . uniqid();
                $filename = $baseName . ".jpg";
                $publicPath = "plates/" . $filename;
                $publicFullPath = storage_path("app/public/" . $publicPath);

                // Ensure plates directory exists
                $platesDir = storage_path("app/public/plates");
                if (!is_dir($platesDir)) {
                    mkdir($platesDir, 0755, true);
                }

                copy($tempPath, $publicFullPath);

                // Copy cropped plate image if available
                $croppedFilename = $baseName . "_cropped.jpg";
                $croppedPublicPath = "plates/" . $croppedFilename;
                if ($croppedPath && file_exists(base_path($croppedPath))) {
                    copy(base_path($croppedPath), storage_path("app/public/" . $croppedPublicPath));
                } else {
                    // Copy full frame as cropped fallback
                    copy($tempPath, storage_path("app/public/" . $croppedPublicPath));
                }

                // Check for Monthly Membership Recognition
                $activeMember = \App\Models\Member::findActiveMemberByPlate($plateNumber);
                $memberRecord = $activeMember ?? \App\Models\Member::findMemberRecordByPlate($plateNumber);
                $isMember = ($activeMember !== null && $activeMember->isCurrentlyActive());
                $memberId = $memberRecord ? $memberRecord->id : null;
                $coveredByMembership = $isMember;

                $remarks = "Auto-detect [{$cameraId}]: YOLOv8 & " . ucfirst($ocrEngine);
                if ($isMember) {
                    $remarks .= " | MEMBER: {$activeMember->full_name} (Expires " . $activeMember->end_date->format('M d, Y') . " — ₱0 Coverage)";
                } elseif ($memberRecord && $memberRecord->isExpired()) {
                    $remarks .= " | EXPIRED MEMBER: {$memberRecord->full_name} (Expired " . $memberRecord->end_date->format('M d, Y') . " — Standard Tariff)";
                } elseif ($memberRecord && $memberRecord->payment_status === 'unpaid') {
                    $remarks .= " | UNPAID MEMBER: {$memberRecord->full_name} (Standard Tariff until dues paid)";
                }

                // Create entry record
                $entry = PlateEntry::create([
                    "session_id" => ParkingSession::getActive()->id,
                    "plate_number" => $plateNumber,
                    "entry_confidence" => $confidence,
                    "entry_time" => $timestamp,
                    "entry_image_path" => $publicPath,
                    "gate_entry" => $gateNumber,
                    "vehicle_type" => $memberRecord ? $memberRecord->vehicle_type : "car",
                    "status" => "entered",
                    "is_member" => $isMember,
                    "member_id" => $memberId,
                    "covered_by_membership" => $coveredByMembership,
                    "remarks" => $remarks,
                ]);

                // Generate QR data for entry pass
                $qrData = $this->generateQrData($plateNumber, $timestamp);
                $qrImage = QrCode::format("svg")
                    ->size(200)
                    ->encoding("UTF-8")
                    ->generate($qrData);
                $qrBase64 = base64_encode($qrImage);

                // Cleanup temp
                if (file_exists($tempPath)) { unlink($tempPath); }

                return response()->json([
                    "success" => true,
                    "type" => "entry",
                    "plate" => $plateNumber,
                    "confidence" => $confidence,
                    "ocr_engine" => $ocrEngine,
                    "timestamp" => $timestamp->toIso8601String(),
                    "formatted_time" => $timestamp->format("M d, Y • h:i:s A"),
                    "gate" => $gateNumber,
                    "camera_id" => $cameraId,
                    "entry_id" => $entry->id,
                    "qr_code" => $qrBase64,
                    "image_url" => asset("storage/" . $publicPath),
                    "is_member" => $isMember,
                    "member_name" => $isMember ? $activeMember->full_name : ($memberRecord ? $memberRecord->full_name : null),
                    "member_status" => $memberRecord ? $memberRecord->computed_status_label : null,
                    "member_expires" => $memberRecord ? $memberRecord->end_date->format('M d, Y') : null,
                    "message" => $isMember 
                        ? "MEMBER {$plateNumber} ({$activeMember->full_name}) entry recorded at {$gateNumber} — ₱0 Coverage"
                        : "Vehicle {$plateNumber} entry recorded at {$gateNumber}",
                ]);
            }

            // ─── EXIT CAMERA ────────────────────────────────────────────
            if ($cameraType === "exit") {
                // Find matching active entry using multi-tier matcher
                $matchResult = $this->findMatchingEnteredVehicle($plateNumber);
                $entry = $matchResult ? $matchResult['entry'] : null;
                $isMatchFound = ($entry !== null);

                // Use the canonical plate number from entry if matched, or the raw read plate
                $effectivePlate = $isMatchFound ? $entry->plate_number : $plateNumber;

                // Deduplication: skip if same plate exited within 60 seconds
                $cleanEffective = strtoupper(preg_replace('/[^A-Z0-9]/', '', $effectivePlate));
                $recentExit = PlateEntry::where('status', 'exited')
                    ->where('exit_time', '>=', now()->subSeconds(60))
                    ->get()
                    ->first(function ($item) use ($cleanEffective) {
                        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $item->plate_number)) === $cleanEffective;
                    });

                if ($recentExit) {
                    if (file_exists($tempPath)) { unlink($tempPath); }

                    return response()->json([
                        "success" => true,
                        "duplicate" => true,
                        "plate" => $effectivePlate,
                        "message" => "Vehicle {$effectivePlate} exit already recorded (dedup)",
                    ]);
                }

                // If not matched, filter out partial/noisy reads when vehicles are currently inside
                $cleanRead = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plateNumber));
                $hasLetters = preg_match('/[A-Z]/', $cleanRead);
                $hasNumbers = preg_match('/[0-9]/', $cleanRead);
                $isValidPlateFormat = ($hasLetters && $hasNumbers && strlen($cleanRead) >= 5);
                $activeVehiclesCount = PlateEntry::where('status', 'entered')->count();

                if (!$isMatchFound && $activeVehiclesCount > 0 && (!$isValidPlateFormat || $confidence < 55.0)) {
                    // Frame was noisy/partial and didn't match any car inside; keep scanning smoothly
                    if (file_exists($tempPath)) { unlink($tempPath); }

                    return response()->json([
                        "success" => false,
                        "no_plate" => true,
                        "message" => "Scanning: Read candidate '{$plateNumber}', waiting for plate matching entered vehicle...",
                    ]);
                }

                // Save image permanently
                $baseName = time() . "_auto_exit_" . uniqid();
                $filename = $baseName . ".jpg";
                $publicPath = "plates/" . $filename;
                $publicFullPath = storage_path("app/public/" . $publicPath);

                $platesDir = storage_path("app/public/plates");
                if (!is_dir($platesDir)) {
                    mkdir($platesDir, 0755, true);
                }

                copy($tempPath, $publicFullPath);

                $croppedFilename = $baseName . "_cropped.jpg";
                $croppedPublicPath = "plates/" . $croppedFilename;
                if ($croppedPath && file_exists(base_path($croppedPath))) {
                    copy(base_path($croppedPath), storage_path("app/public/" . $croppedPublicPath));
                } else {
                    copy($tempPath, storage_path("app/public/" . $croppedPublicPath));
                }

                $activeRate = ParkingRate::getActiveRate();
                $entryTime = null;
                $durationMinutes = 0;
                $totalFeeAmount = (float)$activeRate->base_rate;
                $isFreeGrace = false;
                $formattedDuration = "N/A";

                if ($entry) {
                    $entryTime = $entry->entry_time;
                    $durationMinutes = $entryTime ? $entryTime->diffInMinutes($timestamp) : 0;
                    $tierLabel = $matchResult ? $matchResult['tier'] : 'exact';

                    // Check for Monthly Membership Coverage
                    $activeMember = \App\Models\Member::findActiveMemberByPlate($effectivePlate)
                        ?? \App\Models\Member::findActiveMemberByPlate($entry->plate_number);
                    $isCoveredByMembership = ($activeMember && $activeMember->isCurrentlyActive());

                    if ($isCoveredByMembership) {
                        $totalFeeAmount = 0.00;
                        $isFreeGrace = false;
                        $paymentStatus = "paid";
                        $formattedDuration = $entryTime ? ($entryTime->diff($timestamp)->h . 'h ' . $entryTime->diff($timestamp)->i . 'm') : "0m";
                        $ruleApplied = "MEMBER COVERED: {$activeMember->full_name} (Expires " . $activeMember->end_date->format('M d, Y') . ") — ₱0.00 Fee";
                        $appliedSnapshot = [
                            'type' => 'monthly_membership',
                            'member_id' => $activeMember->id,
                            'member_name' => $activeMember->full_name,
                            'valid_until' => $activeMember->end_date->format('Y-m-d'),
                            'fee' => 0.00,
                        ];
                    } else {
                        // Calculate parking fee with mandatory 15-minute free grace period
                        $feeBreakdown = $activeRate->calculateFee($durationMinutes, $entry->vehicle_type ?? 'car');
                        $totalFeeAmount = $feeBreakdown['fee'];
                        $isFreeGrace = $feeBreakdown['is_free'];
                        $paymentStatus = $isFreeGrace ? "waived" : "unpaid";
                        $formattedDuration = $feeBreakdown['formatted_duration'];
                        $ruleApplied = $feeBreakdown['rule_applied'];
                        $appliedSnapshot = $feeBreakdown['snapshot'];
                    }

                    // Close session
                    $entry->update([
                        "status" => "exited",
                        "exit_time" => $timestamp,
                        "exit_confidence" => $confidence,
                        "exit_image_path" => $publicPath,
                        "gate_exit" => $gateNumber,
                        "duration_minutes" => $durationMinutes,
                        "parking_fee" => $totalFeeAmount,
                        "is_free_grace" => $isFreeGrace,
                        "is_member" => ($activeMember !== null),
                        "member_id" => $activeMember ? $activeMember->id : $entry->member_id,
                        "covered_by_membership" => $isCoveredByMembership,
                        "applied_rate_snapshot" => $appliedSnapshot,
                        "rate_id" => $activeRate->id,
                        "payment_status" => $paymentStatus,
                        "remarks" => trim(($entry->remarks ?? "") . " | Auto-exit [{$cameraId}]: YOLOv8 & " . ucfirst($ocrEngine) . " (Matched: {$tierLabel}) — " . $ruleApplied),
                    ]);
                } else {
                    // No matching entry — log as exceptional exit-only detection
                    $totalFeeAmount = (float)$activeRate->base_rate;
                    $isFreeGrace = false;
                    $durationMinutes = 0;
                    $formattedDuration = "0m (Missing Entry)";

                    $entry = PlateEntry::create([
                        "session_id" => ParkingSession::getActive()->id,
                        "plate_number" => $plateNumber,
                        "entry_confidence" => 0.00,
                        "exit_confidence" => $confidence,
                        "entry_time" => $timestamp,
                        "exit_time" => $timestamp,
                        "exit_image_path" => $publicPath,
                        "gate_entry" => "Unknown",
                        "gate_exit" => $gateNumber,
                        "duration_minutes" => 0,
                        "parking_fee" => $totalFeeAmount,
                        "is_free_grace" => false,
                        "applied_rate_snapshot" => [
                            'note' => 'Exceptional: Exit without entry record',
                            'base_rate' => $totalFeeAmount,
                        ],
                        "rate_id" => $activeRate->id,
                        "payment_status" => "unpaid",
                        "status" => "exited",
                        "remarks" => "Auto-exit only [{$cameraId}]: No active entry record. Assessed base fee ₱" . number_format($totalFeeAmount, 2),
                    ]);
                }

                if (isset($isCoveredByMembership) && $isCoveredByMembership) {
                    $formattedFee = "₱0.00 (MEMBER PASS)";
                } else {
                    $formattedFee = $isFreeGrace ? "₱0.00 (FREE — 15m Grace)" : "₱" . number_format($totalFeeAmount, 2);
                }

                // Generate payment QR
                $paymentPayload = json_encode([
                    "type" => "PARKING_PAYMENT",
                    "facility" => "Autotrace Parking",
                    "plate" => $effectivePlate,
                    "time_in" => $entryTime ? $entryTime->format("Y-m-d H:i:s") : "N/A",
                    "time_out" => $timestamp->format("Y-m-d H:i:s"),
                    "duration_minutes" => $durationMinutes,
                    "amount" => $totalFeeAmount,
                    "currency" => "PHP",
                    "payment_mode" => "DIGITAL_QR_PAYMENT",
                ]);

                $paymentQrImage = QrCode::format("svg")
                    ->size(200)
                    ->encoding("UTF-8")
                    ->generate($paymentPayload);
                $paymentQrCode = base64_encode($paymentQrImage);

                // Cleanup temp
                if (file_exists($tempPath)) { unlink($tempPath); }

                return response()->json([
                    "success" => true,
                    "type" => "exit",
                    "plate" => $effectivePlate,
                    "detected_plate" => $plateNumber,
                    "confidence" => $confidence,
                    "ocr_engine" => $ocrEngine,
                    "timestamp" => $timestamp->toIso8601String(),
                    "formatted_time" => $timestamp->format("M d, Y • h:i:s A"),
                    "gate" => $gateNumber,
                    "camera_id" => $cameraId,
                    "entry_id" => $entry->id,
                    "is_match_found" => $isMatchFound,
                    "match_tier" => $matchResult['tier'] ?? null,
                    "entry_gate" => $isMatchFound ? ($entry->gate_entry ?? "Gate 1") : "Unknown",
                    "entry_time" => $entryTime ? $entryTime->format("M d, Y • h:i:s A") : null,
                    "duration" => $formattedDuration,
                    "duration_minutes" => $durationMinutes,
                    "parking_fee" => $totalFeeAmount,
                    "formatted_fee" => $formattedFee,
                    "payment_qr" => $paymentQrCode,
                    "image_url" => asset("storage/" . $publicPath),
                    "message" => $isMatchFound
                        ? "Vehicle {$effectivePlate} matched from Entry & recorded exit — Fee: {$formattedFee}"
                        : "Vehicle {$effectivePlate} exit recorded — Fee: {$formattedFee}",
                ]);
            }

        } catch (\Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            return response()->json([
                "success" => false,
                "error" => "Auto-detect error: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * End active parking session, archive summary, and start a new blank session.
     */
    public function endSession(Request $request)
    {
        try {
            $activeSession = ParkingSession::getActive();
            $metrics = $activeSession->calculateMetrics();

            // Save and archive completed session summary
            $activeSession->update([
                'ended_at' => now(),
                'status' => 'ended',
                'total_revenue' => $metrics['total_revenue'],
                'total_vehicles' => $metrics['total_vehicles'],
                'currently_parked' => $metrics['currently_parked'],
                'completed_sessions' => $metrics['completed_sessions'],
                'total_duration_minutes' => $metrics['total_duration_minutes'],
                'notes' => $request->input('notes'),
            ]);

            // Start a completely new blank parking session
            $count = ParkingSession::count() + 1;
            $newSession = ParkingSession::create([
                'session_code' => 'SES-' . date('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT),
                'started_at' => now(),
                'status' => 'active',
                'total_revenue' => 0.00,
                'total_vehicles' => 0,
                'currently_parked' => 0,
                'completed_sessions' => 0,
                'total_duration_minutes' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Parking session ended and archived successfully.',
                'summary' => [
                    'session_id' => $activeSession->id,
                    'session_code' => $activeSession->session_code,
                    'total_revenue' => $metrics['total_revenue'],
                    'formatted_revenue' => $metrics['formatted_revenue'],
                    'total_vehicles' => $metrics['total_vehicles'],
                    'currently_parked' => $metrics['currently_parked'],
                    'completed_sessions' => $metrics['completed_sessions'],
                    'total_duration_minutes' => $metrics['total_duration_minutes'],
                    'formatted_duration' => $metrics['formatted_duration'],
                    'started_at' => $metrics['session_start_time'],
                    'ended_at' => $metrics['session_end_time'],
                ],
                'new_session' => [
                    'id' => $newSession->id,
                    'session_code' => $newSession->session_code,
                    'started_at' => $newSession->started_at->format('M d, Y • h:i:s A'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to end session: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retrieve all historical archived parking sessions.
     */
    public function sessionHistory(Request $request)
    {
        $sessions = ParkingSession::where('status', 'ended')
            ->orderBy('ended_at', 'DESC')
            ->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions->map(function ($s) {
                $hours = intdiv($s->total_duration_minutes, 60);
                $mins = $s->total_duration_minutes % 60;
                $formattedDuration = $hours > 0 ? "{$hours}h {$mins}m" : ($mins > 0 ? "{$mins}m" : "0m");
                return [
                    'id' => $s->id,
                    'session_code' => $s->session_code,
                    'started_at' => $s->started_at ? $s->started_at->format('M d, Y • h:i A') : 'N/A',
                    'ended_at' => $s->ended_at ? $s->ended_at->format('M d, Y • h:i A') : 'N/A',
                    'total_revenue' => '₱' . number_format($s->total_revenue, 2),
                    'total_vehicles' => $s->total_vehicles,
                    'currently_parked' => $s->currently_parked,
                    'completed_sessions' => $s->completed_sessions,
                    'duration' => $formattedDuration,
                ];
            }),
        ]);
    }

    /**
     * Find an active entered vehicle matching the detected exit plate.
     * Uses multi-tier matching:
     * 1. Exact alphanumeric normalization (removes spaces, dashes, symbols)
     * 2. Visual OCR confusion canonicalization (O/0, I/1/L, B/8/D, S/5, Z/2, G/6)
     * 3. Substring & prefix/suffix matching (for plates with clipped/extra characters)
     * 4. Levenshtein edit distance (<= 1 for length >= 4, <= 2 for length >= 6)
     *
     * @param string $rawExitPlate
     * @return array{entry: PlateEntry, tier: string, score: float}|null
     */
    private function findMatchingEnteredVehicle(string $rawExitPlate): ?array
    {
        $cleanExit = strtoupper(preg_replace('/[^A-Z0-9]/', '', $rawExitPlate));
        if (strlen($cleanExit) < 3) {
            return null;
        }

        // Fetch all vehicles currently entered in the facility
        // Prioritize current active session first, then older active entries
        $activeSession = ParkingSession::getActive();
        $activeSessionId = $activeSession ? $activeSession->id : 0;

        $enteredEntries = PlateEntry::where('status', 'entered')
            ->orderByRaw("CASE WHEN session_id = ? THEN 0 ELSE 1 END", [$activeSessionId])
            ->latest('entry_time')
            ->get();

        if ($enteredEntries->isEmpty()) {
            return null;
        }

        // Tier 1: Exact normalized alphanumeric match (e.g. "NBC 1234" == "NBC1234" == "NBC-1234")
        foreach ($enteredEntries as $entry) {
            $cleanEntry = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entry->plate_number));
            if ($cleanEntry === $cleanExit) {
                return ['entry' => $entry, 'tier' => 'exact', 'score' => 1.0];
            }
        }

        // Tier 2: OCR visual confusion equivalence
        // E.g. '0' <-> 'O', '1' <-> 'I', '8' <-> 'B' <-> 'D', '5' <-> 'S', '2' <-> 'Z', '6' <-> 'G'
        $ocrReplacements = [
            '0' => 'O', '1' => 'I', 'L' => 'I', '8' => 'B',
            'D' => 'B', '5' => 'S', '2' => 'Z', '6' => 'G',
        ];
        $exitSkeleton = strtr($cleanExit, $ocrReplacements);

        foreach ($enteredEntries as $entry) {
            $cleanEntry = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entry->plate_number));
            $entrySkeleton = strtr($cleanEntry, $ocrReplacements);
            if ($entrySkeleton === $exitSkeleton) {
                return ['entry' => $entry, 'tier' => 'ocr_skeleton', 'score' => 0.95];
            }
        }

        // Tier 3: Substring / Prefix / Suffix match
        // E.g. Exit read "NBC123" and entered was "NBC1234", or vice versa
        foreach ($enteredEntries as $entry) {
            $cleanEntry = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entry->plate_number));
            $lenExit = strlen($cleanExit);
            $lenEntry = strlen($cleanEntry);
            if ($lenExit >= 4 && $lenEntry >= 4) {
                if (str_contains($cleanExit, $cleanEntry) || str_contains($cleanEntry, $cleanExit)) {
                    return ['entry' => $entry, 'tier' => 'substring', 'score' => 0.90];
                }
            }
        }

        // Tier 4: Levenshtein edit distance
        // Matches plates with 1-2 character variation (lighting, angled plate, motion blur)
        $bestMatch = null;
        $bestDist = 999;

        foreach ($enteredEntries as $entry) {
            $cleanEntry = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entry->plate_number));
            $dist = levenshtein($cleanExit, $cleanEntry);
            $maxLen = max(strlen($cleanExit), strlen($cleanEntry));
            $allowedDist = ($maxLen >= 6) ? 2 : 1;

            if ($dist <= $allowedDist && $dist < $bestDist) {
                $bestDist = $dist;
                $bestMatch = $entry;
            }
        }

        if ($bestMatch) {
            return ['entry' => $bestMatch, 'tier' => 'levenshtein_' . $bestDist, 'score' => 0.85];
        }

        return null;
    }
}
