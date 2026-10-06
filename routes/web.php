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

    // Parking Session Management (End Session & Archives)
    Route::post("/parking-session/end", [PlateOcrController::class, "endSession"])->name("parking-session.end");
    Route::get("/parking-session/history", [PlateOcrController::class, "sessionHistory"])->name("parking-session.history");

    // Analytics & Operational Reports
    Route::get("/analytics", [\App\Http\Controllers\AnalyticsController::class, "index"])->name("analytics");
    Route::get("/analytics/export", [\App\Http\Controllers\AnalyticsController::class, "exportCsv"])->name("analytics.export");

    // Settings Route
    Route::get("/settings", function () {
        return view("settings");
    })->name("settings");

});
