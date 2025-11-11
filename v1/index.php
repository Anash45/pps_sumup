<?php
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// --- Step 1: Generate QR code as PNG with transparent background ---
$tempQRPath = __DIR__ . '/qr_temp.png';
$options = new QROptions([
    'outputType'      => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel'        => QRCode::ECC_M,   // Error correction level M (15%)
    'scale'           => 15,
    'quietzoneSize'   => 0,               // remove white margin
    'imageTransparent'=> true,            // make background transparent
]);
$qrcode = new QRCode($options);
$qrcode->render(
    'https://www.sumup.io/sn/ya69h?utm_source=strut_1&utm_medium=offline&utm_campaign=local',
    $tempQRPath
);

// --- Step 2: Load PDF template ---
$pdf = new Fpdi();
$templatePath = __DIR__ . '/template.pdf';
$pageCount = $pdf->setSourceFile($templatePath);

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $tplId = $pdf->importPage($pageNo);
    $size = $pdf->getTemplateSize($tplId);

    // Add page with same size as template
    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $pdf->useTemplate($tplId);

    // --- Step 3: Place QR code ---
    $width = 46;   // QR width in mm
    $height = 46;  // QR height in mm

    // Horizontal center
    $x = ($size['width'] - $width) / 2;

    // Vertical position from bottom (adjustable)
    $yFromBottom = 41.5;
    $y = $size['height'] - $yFromBottom - $height;

    $pdf->Image($tempQRPath, $x, $y, $width, $height, 'PNG');
}

// --- Step 4: Output PDF ---
$outputPath = __DIR__ . '/output.pdf';
$pdf->Output($outputPath, 'F');

unlink($tempQRPath);

echo "PDF generated successfully with transparent QR code, centered horizontally, ECC M!\n";
