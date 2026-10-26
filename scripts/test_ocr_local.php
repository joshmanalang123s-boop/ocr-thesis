<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(\App\Http\Controllers\PlateOcrController::class);

$reflection = new \ReflectionClass($controller);
$method = $reflection->getMethod('processPlateImage');
$method->setAccessible(true);

$images = [
    'Green Plate 1' => storage_path('app/public/plates/1791420446_auto_entry_6ac6e81e43948.jpg'),
    'Green Plate 2' => storage_path('app/public/plates/1791420576_auto_entry_6ac6e8a0cf86a.jpg'),
    'White Plate'   => storage_path('app/public/plates/1791348644_auto_entry_6ac5cfa47afd2.jpg'),
];

foreach ($images as $label => $imagePath) {
    echo "\n====================\nTesting $label ($imagePath)\n";
    $result = $method->invoke($controller, $imagePath);
    echo "Plate: " . ($result['plate_text'] ?? 'FAILED') . " | Conf: " . ($result['confidence'] ?? 0) . "%\n";
    echo "Crop: " . ($result['cropped_path'] ?? 'none') . "\n";
}
