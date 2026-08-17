<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\PlateOcrController;

// License Plate OCR Routes
Route::get("/plate-ocr", [PlateOcrController::class, "index"])->name(
    "plate-ocr.index",
);
Route::post("/plate-ocr/detect", [PlateOcrController::class, "detect"])->name(
    "plate-ocr.detect",
);
Route::get("/plate-ocr/detect", function () {
    return redirect()->route("plate-ocr.index");
});
Route::get("/plate-ocr/download", [
    PlateOcrController::class,
    "downloadQr",
])->name("plate-ocr.download");
Route::post("/plate-ocr/stream", [
    PlateOcrController::class,
    "detectFromStream",
])->name("plate-ocr.stream");
Route::get("/plate-ocr/history", [
    PlateOcrController::class,
    "history",
])->name("plate-ocr.history");
Route::get("/plate-ocr/print/{id}", [
    PlateOcrController::class,
    "printTicket",
])->name("plate-ocr.print");

// Dual Live Cameras (Gate 1 & Gate 2 simultaneous stream)
Route::get("/plate-ocr/dual", [
    PlateOcrController::class,
    "dualIndex",
])->name("plate-ocr.dual");

// Exit Camera Routes (separate hardware camera for exit)
Route::get("/plate-ocr/exit", [
    PlateOcrController::class,
    "exitIndex",
])->name("plate-ocr.exit-scan");
Route::post("/plate-ocr/exit/detect", [
    PlateOcrController::class,
    "detectExit",
])->name("plate-ocr.exit-detect");
Route::get("/plate-ocr/exit/detect", function () {
    return redirect()->route("plate-ocr.exit-scan");
});

// Manual exit vehicle route (from dashboard/history table)
Route::post("/plate-ocr/{id}/exit", [
    PlateOcrController::class,
    "exitVehicle",
])->name("plate-ocr.exit");

// Dashboard shortcut for admins
Route::get("/dashboard", [PlateOcrController::class, "dashboard"])->name(
    "dashboard",
);

// Settings Route
Route::get("/settings", function () {
    return view("settings");
})->name("settings");

// OCR Routes (Disabled)
// Route::get("/ocr", [OcrController::class, "index"])->name("ocr.index");
// Route::post("/ocr/process", [OcrController::class, "process"])->name(
//     "ocr.process",
// );
// Route::post("/ocr/download", [OcrController::class, "download"])->name(
//     "ocr.download",
// );
// Route::get("/ocr/history", [OcrController::class, "history"])->name(
//     "ocr.history",
// );

// Welcome route
Route::get("/", function () {
    return redirect()->route("plate-ocr.index");
});
