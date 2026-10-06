<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PlateEntry;
use Illuminate\Support\Facades\Storage;

$platesDir = storage_path('app/public/plates');
if (!is_dir($platesDir)) {
    mkdir($platesDir, 0755, true);
}

function generatePlateImage(string $plateNumber, string $outputPath, string $croppedOutputPath): void
{
    $w = 320;
    $h = 100;

    $im = imagecreatetruecolor($w, $h);
    imageantialias($im, true);

    // Car bumper background (dark textured charcoal)
    $bumperBg = imagecolorallocate($im, 24, 30, 42);
    imagefilledrectangle($im, 0, 0, $w, $h, $bumperBg);

    // Add subtle bumper grille horizontal lines
    $grilleLine = imagecolorallocate($im, 15, 20, 30);
    for ($y = 0; $y < $h; $y += 6) {
        imageline($im, 0, $y, $w, $y, $grilleLine);
    }

    // Plate position within bumper crop
    $px1 = 20;
    $py1 = 12;
    $px2 = $w - 20;
    $py2 = $h - 12;

    // Plate shadow
    $shadow = imagecolorallocatealpha($im, 0, 0, 0, 80);
    imagefilledrectangle($im, $px1 + 2, $py1 + 3, $px2 + 2, $py2 + 3, $shadow);

    // Plate outer black frame
    $plateFrame = imagecolorallocate($im, 17, 24, 39);
    imagefilledrectangle($im, $px1, $py1, $px2, $py2, $plateFrame);

    // Plate background (metallic reflective white/cream)
    $plateBg = imagecolorallocate($im, 248, 250, 252);
    imagefilledrectangle($im, $px1 + 3, $py1 + 3, $px2 - 3, $py2 - 3, $plateBg);

    // Embossed inner border line
    $innerBorder = imagecolorallocate($im, 30, 41, 59);
    imagerectangle($im, $px1 + 6, $py1 + 6, $px2 - 6, $py2 - 6, $innerBorder);

    // Mounting bolts (left and right)
    $boltColor = imagecolorallocate($im, 100, 116, 139);
    $boltCenter = imagecolorallocate($im, 51, 65, 85);
    imagefilledellipse($im, $px1 + 18, $py1 + 14, 8, 8, $boltColor);
    imagefilledellipse($im, $px1 + 18, $py1 + 14, 4, 4, $boltCenter);
    imagefilledellipse($im, $px2 - 18, $py1 + 14, 8, 8, $boltColor);
    imagefilledellipse($im, $px2 - 18, $py1 + 14, 4, 4, $boltCenter);

    // Header label ("AUTOTRACE")
    $headerColor = imagecolorallocate($im, 71, 85, 105);
    $headerText = "AUTOTRACE";
    imagestring($im, 2, ($w - (strlen($headerText) * 6)) / 2, $py1 + 9, $headerText, $headerColor);

    // Plate number text (bold embossed style)
    $textColor = imagecolorallocate($im, 15, 23, 42);
    $textShadow = imagecolorallocate($im, 203, 213, 225);

    // Using built-in font size 5
    $font = 5;
    $charWidth = imagefontwidth($font);
    $charHeight = imagefontheight($font);
    $textLen = strlen($plateNumber);
    $textX = (int)(($w - ($textLen * $charWidth * 1.6)) / 2);
    $textY = (int)($py1 + 32);

    // Render enlarged & embossed font
    for ($i = 0; $i < $textLen; $i++) {
        $char = $plateNumber[$i];
        $cx = $textX + ($i * (int)($charWidth * 1.65));
        
        // Emboss shadow
        imagestring($im, $font, $cx + 1, $textY + 1, $char, $textShadow);
        // Main character
        imagestring($im, $font, $cx, $textY, $char, $textColor);
        imagestring($im, $font, $cx + 1, $textY, $char, $textColor);
    }

    // Save full image
    imagejpeg($im, $outputPath, 92);

    // Also create tight cropped version (just the license plate itself)
    $cropW = $px2 - $px1;
    $cropH = $py2 - $py1;
    $cropIm = imagecreatetruecolor($cropW, $cropH);
    imagecopy($cropIm, $im, 0, 0, $px1, $py1, $cropW, $cropH);
    imagejpeg($cropIm, $croppedOutputPath, 95);

    imagedestroy($cropIm);
    imagedestroy($im);
}

$entries = PlateEntry::all();
$count = 0;

foreach ($entries as $entry) {
    $hasValidImage = false;
    if ($entry->entry_image_path) {
        $cropFile = str_replace('.', '_cropped.', $entry->entry_image_path);
        if (Storage::disk('public')->exists($cropFile) || Storage::disk('public')->exists($entry->entry_image_path)) {
            $hasValidImage = true;
        }
    }

    if (!$hasValidImage) {
        $plate = $entry->plate_number ?: 'AUTOTRACE';
        $baseName = time() . '_plate_' . md5($entry->id . '_' . $plate);
        $fullPath = storage_path('app/public/plates/' . $baseName . '.jpg');
        $cropPath = storage_path('app/public/plates/' . $baseName . '_cropped.jpg');

        generatePlateImage($plate, $fullPath, $cropPath);

        $entry->entry_image_path = 'plates/' . $baseName . '.jpg';
        $entry->save();
        $count++;
        echo "Generated plate image for [{$entry->id}] {$plate} -> {$baseName}.jpg\n";
    }
}

echo "Successfully processed. Generated {$count} plate images.\n";
