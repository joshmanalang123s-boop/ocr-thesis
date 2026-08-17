<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(\App\Http\Controllers\PlateOcrController::class);

$reflection = new \ReflectionClass($controller);
$method = $reflection->getMethod('processPlateImage');
$method->setAccessible(true);

$imagePath = storage_path('app/public/plates/1785437156_exit_plate_6a6b9be421963.jpg');
echo "Testing plate detection for: $imagePath\n";

$result = $method->invoke($controller, $imagePath);
echo "Result:\n";
print_r($result);
