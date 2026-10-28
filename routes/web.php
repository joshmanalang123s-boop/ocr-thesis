<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\PlateOcrController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get("/login", [AuthController::class, "showLoginForm"])->name("login");
Route::post("/login", [AuthController::class, "login"]);
Route::post("/logout", [AuthController::class, "logout"])->name("logout");
Route::get("/logout", [AuthController::class, "logout"]);

/*
|--------------------------------------------------------------------------
| Protected Application Routes (Requires Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Welcome route
    Route::get("/", function () {
        return redirect()->route("dashboard");
    });

    // Dashboard shortcut
    Route::get("/dashboard", [PlateOcrController::class, "dashboard"])->name("dashboard");

    // License Plate OCR Routes
    Route::get("/plate-ocr", [PlateOcrController::class, "index"])->name("plate-ocr.index");
    Route::post("/plate-ocr/detect", [PlateOcrController::class, "detect"])->name("plate-ocr.detect");
    Route::get("/plate-ocr/detect", function () {
        return redirect()->route("plate-ocr.index");
    });
    Route::get("/plate-ocr/download", [PlateOcrController::class, "downloadQr"])->name("plate-ocr.download");
    Route::post("/plate-ocr/stream", [PlateOcrController::class, "detectFromStream"])->name("plate-ocr.stream");
    Route::get("/plate-ocr/history", [PlateOcrController::class, "history"])->name("plate-ocr.history");
    Route::get("/plate-ocr/print/{id}", [PlateOcrController::class, "printTicket"])->name("plate-ocr.print");

    // Dual Live Cameras (Gate 1 & Gate 2 simultaneous stream)
    Route::get("/plate-ocr/dual", [PlateOcrController::class, "dualIndex"])->name("plate-ocr.dual");

    // Exit Camera Routes (separate hardware camera for exit)
    Route::get("/plate-ocr/exit", [PlateOcrController::class, "exitIndex"])->name("plate-ocr.exit-scan");
    Route::post("/plate-ocr/exit/detect", [PlateOcrController::class, "detectExit"])->name("plate-ocr.exit-detect");
    Route::get("/plate-ocr/exit/detect", function () {
        return redirect()->route("plate-ocr.exit-scan");
    });

    // Automated camera detection API (AJAX endpoint)
    Route::post("/api/auto-detect", [PlateOcrController::class, "autoDetect"])->name("api.auto-detect");

    // Manual exit vehicle route (from dashboard/history table)
    Route::post("/plate-ocr/{id}/exit", [PlateOcrController::class, "exitVehicle"])->name("plate-ocr.exit");

    // Correct plate number (Staff verification & audit)
    Route::post("/plate-ocr/{id}/correct", [PlateOcrController::class, "correctPlate"])->name("plate-ocr.correct");

    // Parking Rates Configuration & Audits (Admin only)
    Route::get("/admin/parking-rates", [\App\Http\Controllers\ParkingFeeController::class, "index"])->name("parking-rates.index");
    Route::get("/admin/parking-rates/json", [\App\Http\Controllers\ParkingFeeController::class, "getRatesJson"])->name("parking-rates.json");
    Route::post("/admin/parking-rates", [\App\Http\Controllers\ParkingFeeController::class, "update"])->name("parking-rates.update");

    // Parking Session Management (End Session & Archives)
    Route::post("/parking-session/end", [PlateOcrController::class, "endSession"])->name("parking-session.end");
    Route::get("/parking-session/history", [PlateOcrController::class, "sessionHistory"])->name("parking-session.history");

    // Analytics & Cash Flow Reports
    Route::get("/analytics", [\App\Http\Controllers\AnalyticsController::class, "index"])->name("analytics");
    Route::get("/cash-flow", [\App\Http\Controllers\AnalyticsController::class, "index"])->name("cash-flow");
    Route::get("/analytics/export", [\App\Http\Controllers\AnalyticsController::class, "exportCsv"])->name("analytics.export");

    // Settings Route
    Route::get("/settings", function () {
        $activeRate = \App\Models\ParkingRate::getActiveRate();
        $rateAudits = \App\Models\ParkingRateAudit::latest('created_at')->take(10)->get();
        return view("settings", compact("activeRate", "rateAudits"));
    })->name("settings");

    // Monthly Membership Management Routes
    Route::get("/memberships", [\App\Http\Controllers\MembershipController::class, "index"])->name("memberships.index");
    Route::post("/memberships", [\App\Http\Controllers\MembershipController::class, "store"])->name("memberships.store");
    Route::get("/memberships/{id}", [\App\Http\Controllers\MembershipController::class, "show"])->name("memberships.show");
    Route::put("/memberships/{id}", [\App\Http\Controllers\MembershipController::class, "update"])->name("memberships.update");
    Route::post("/memberships/{id}/renew", [\App\Http\Controllers\MembershipController::class, "renew"])->name("memberships.renew");
    Route::post("/memberships/{id}/confirm-payment", [\App\Http\Controllers\MembershipController::class, "confirmPayment"])->name("memberships.confirm-payment");
    Route::post("/memberships/{id}/toggle-status", [\App\Http\Controllers\MembershipController::class, "toggleStatus"])->name("memberships.toggle-status");
    Route::post("/memberships/settings", [\App\Http\Controllers\MembershipController::class, "updateSettings"])->name("memberships.settings.update");
    Route::get("/memberships-export", [\App\Http\Controllers\MembershipController::class, "exportCsv"])->name("memberships.export");

});
