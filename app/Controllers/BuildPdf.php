<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class BuildPdf extends Controller
{
    public function index()
    {
        helper('filesystem');

        ini_set('memory_limit', '512M');

        $language = $this->request->getPost('language') ?? 'de';
        $file = $this->request->getFile('csv_file');

        $urls = [];

        if ($file && $file->isValid() && $file->getClientMimeType() === 'text/csv') {
            $filePath = $file->getTempName();
            $rows = array_map('str_getcsv', file($filePath));

            if (!empty($rows)) {
                $headers = array_shift($rows);

                $urlColumnIndex = array_search('URL', $headers);
                if ($urlColumnIndex === false) {
                    session()->setFlashdata('message', 'URLs are not present in the CSV file.');
                    return redirect()->back()->withInput();
                }

                foreach ($rows as $row) {
                    if (isset($row[$urlColumnIndex]) && !empty($row[$urlColumnIndex])) {
                        $urls[] = $row[$urlColumnIndex];
                    }
                }
            } else {
                session()->setFlashdata('message', 'CSV file is empty.');
                return redirect()->back()->withInput();
            }
        } else {
            session()->setFlashdata('message', 'Please upload a valid CSV file.');
            return redirect()->back()->withInput();
        }

        // PDF page size
        $pageWidthMM = 1046;
        $pageHeightMM = 1480;
        $pageWidthPt = $pageWidthMM * 2.83465;
        $pageHeightPt = $pageHeightMM * 2.83465;

        // Card size
        $cardWidthMM = 148;
        $cardHeightMM = 210;
        $cardWidthPt = $cardWidthMM * 2.83465;
        $cardHeightPt = $cardHeightMM * 2.83465;

        $rowsPerPage = 7;
        $colsPerPage = 7;
        $cardsPerPage = $rowsPerPage * $colsPerPage;

        // Margins and spacing
        $xStart = 5 * 2.83465;
        $yStart = 5 * 2.83465;
        $xSpacing = 0;
        $ySpacing = 0;

        $saveDir = WRITEPATH . 'pdfs/';
        if (!is_dir($saveDir))
            mkdir($saveDir, 0777, true);

        $fileName = 'cards.pdf';
        $filePath = $saveDir . $fileName;

        $pdf = new Fpdi('P', 'pt', [$pageWidthPt, $pageHeightPt]);

        // Sample card template
        $samplePdf = WRITEPATH . "sample_{$language}.pdf";
        if (!file_exists($samplePdf))
            die('Sample PDF not found: ' . $samplePdf);

        $pdf->setSourceFile($samplePdf);
        $tplId = $pdf->importPage(1);

        $totalCards = ceil(count($urls) / $cardsPerPage) * $cardsPerPage; // round up to full pages
        $cardNumber = 1;


        for ($i = 0; $i < $totalCards; $i++) {

            // Start new page every 49 cards
            if (($i % $cardsPerPage) === 0) {
                $pdf->AddPage();

                // Draw vertical full-height lines
                for ($col = 1; $col < $colsPerPage; $col++) {
                    $xLine = $xStart + $col * $cardWidthPt - 0.5;
                    $pdf->SetFillColor(0, 0, 0);
                    $pdf->Rect($xLine, 0, 1, $pageHeightPt, 'F');
                }

                // Draw horizontal full-width lines
                for ($row = 1; $row < $rowsPerPage; $row++) {
                    $yLine = $yStart + $row * $cardHeightPt - 0.5;
                    $pdf->SetFillColor(0, 0, 0);
                    $pdf->Rect(0, $yLine, $pageWidthPt, 1, 'F');
                }

                // Extra edge lines
                $pdf->Rect(0, 7 * 2.83465, $pageWidthPt, 1, 'F'); // top
                $pdf->Rect(0, $pageHeightPt - 7 * 2.83465, $pageWidthPt, 1, 'F'); // bottom
                $pdf->Rect(7 * 2.83465, 0, 1, $pageHeightPt, 'F'); // left
                $pdf->Rect($pageWidthPt - 7 * 2.83465, 0, 1, $pageHeightPt, 'F'); // right
            }

            $row = floor(($i % $cardsPerPage) / $colsPerPage);
            $col = ($i % $cardsPerPage) % $colsPerPage;

            $xCard = $xStart + $col * ($cardWidthPt + $xSpacing);
            $yCard = $yStart + $row * ($cardHeightPt + $ySpacing);

            // Check if there is a URL for this card
            if (isset($urls[$i])) {
                // Place sample card template
                $pdf->useTemplate($tplId, $xCard, $yCard, $cardWidthPt, $cardHeightPt);

                // Generate QR code
                try {
                    $tempQRPath = WRITEPATH . "qr_card_{$cardNumber}.png";
                    $qrUrl = $urls[$i];
                    $options = new QROptions([
                        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                        'eccLevel' => QRCode::ECC_M,
                        'scale' => 20,
                        'quietzoneSize' => 0,
                        'imageTransparent' => true,
                    ]);
                    $qrcode = new QRCode($options);
                    $qrcode->render($qrUrl, $tempQRPath);

                    // QR placement relative to card
                    $qrWidthMM = 46;
                    $qrHeightMM = 46;
                    $xOffsetMM = ($cardWidthMM - $qrWidthMM) / 2;
                    $yOffsetFromBottomMM = 42;
                    $xOffsetPt = $xOffsetMM * 2.83465;
                    $yOffsetPt = ($cardHeightMM - $yOffsetFromBottomMM - $qrHeightMM) * 2.83465;
                    $qrWidthPt = $qrWidthMM * 2.83465;
                    $qrHeightPt = $qrHeightMM * 2.83465;

                    $pdf->Image(
                        $tempQRPath,
                        $xCard + $xOffsetPt,
                        $yCard + $yOffsetPt,
                        $qrWidthPt,
                        $qrHeightPt,
                        'PNG'
                    );

                    if (file_exists($tempQRPath))
                        unlink($tempQRPath);

                } catch (\Exception $e) {
                    log_message('error', "QR generation failed for card {$cardNumber}: " . $e->getMessage());
                }
            } else {
                // No URL available — create blank white card
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect($xCard, $yCard, $cardWidthPt, $cardHeightPt, 'F');
            }

            $cardNumber++;
        }

        $pdf->Output($filePath, 'F');

        $downloadUrl = base_url('download/' . $fileName);
        session()->setFlashdata('pdf_link', $downloadUrl);
        session()->setFlashdata('message', 'PDF generated with QR codes for all URLs!');

        return redirect()->to('/');
    }

    // Download route
    public function download($file)
    {
        $filePath = WRITEPATH . 'pdfs/' . $file;
        if (file_exists($filePath)) {
            return $this->response->download($filePath, null);
        }
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
}
