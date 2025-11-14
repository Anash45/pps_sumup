<?php

namespace App\Http\Controllers;

use App\Models\SamplePdf;
use Illuminate\Http\Request;
use Log;
use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class MyPdfController extends Controller
{
    public function build(Request $request)
    {
        $request->validate([
            'samplePdf' => 'required|integer|exists:sample_pdfs,id',
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $samplePdfId = $request->input('samplePdf');
        $file = $request->file('csv_file');

        // Get sample PDF from DB
        $samplePdfRecord = SamplePdf::find($samplePdfId);

        if (!$samplePdfRecord) {
            return response()->json([
                'success' => false,
                'message' => "Sample PDF not found in database."
            ]);
        }

        // Full private storage path
        $samplePdfPath = storage_path('app/private/' . ltrim($samplePdfRecord->path, '/'));

        if (!file_exists($samplePdfPath)) {
            return response()->json([
                'success' => false,
                'message' => "Sample PDF file does not exist on server."
            ]);
        }

        // Use this in FPDI
        $samplePdf = $samplePdfPath;


        /** -------------------------
         *   READ CSV
         * ------------------------- */
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'CSV file is empty.']);
        }

        $headers = array_shift($rows);
        $urlColumnIndex = array_search('URL', $headers);
        if ($urlColumnIndex === false) {
            return response()->json(['success' => false, 'message' => 'URLs not found in CSV.']);
        }

        $urls = [];
        foreach ($rows as $row) {
            if (!empty($row[$urlColumnIndex])) {
                $urls[] = trim($row[$urlColumnIndex]);
            }
        }

        /** -------------------------
         *   PDF DIMENSIONS AND GRID
         * ------------------------- */
        $pageWidthMM = 1046;
        $pageHeightMM = 1480;
        $pageWidthPt = $pageWidthMM * 2.83465;
        $pageHeightPt = $pageHeightMM * 2.83465;

        $cardWidthMM = 148;
        $cardHeightMM = 210;
        $cardWidthPt = $cardWidthMM * 2.83465;
        $cardHeightPt = $cardHeightMM * 2.83465;

        $rowsPerPage = 7;
        $colsPerPage = 7;
        $cardsPerPage = $rowsPerPage * $colsPerPage; // 49
        $maxPagesPerFile = 10;
        $cardsPerFile = $cardsPerPage * $maxPagesPerFile; // 490

        $xStart = 5 * 2.83465;
        $yStart = 5 * 2.83465;

        /** -------------------------
         *   STORAGE CLEANUP
         * ------------------------- */
        $saveDir = storage_path('app/public/pdfs');
        if (!is_dir($saveDir))
            mkdir($saveDir, 0777, true);

        foreach (glob($saveDir . '/*.pdf') as $oldFile) {
            if (time() - filemtime($oldFile) > 86400)
                @unlink($oldFile);
        }

        /** -------------------------
         *   BUILD PAGES COUNT
         * ------------------------- */
        $totalUrlCount = count($urls);
        $totalCardSlots = ceil($totalUrlCount / $cardsPerPage) * $cardsPerPage;
        $totalFiles = ceil($totalCardSlots / $cardsPerFile);

        $generatedFiles = [];
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cardNumber = 0;

        /** ============================================================
         *   MAIN LOOP — FOR EACH PDF FILE
         * ============================================================ */
        for ($part = 0; $part < $totalFiles; $part++) {

            $pdf = new Fpdi('P', 'pt', [$pageWidthPt, $pageHeightPt]);
            $pdf->setSourceFile($samplePdf);
            $tplId = $pdf->importPage(1);

            $start = $part * $cardsPerFile;
            $end = min($start + $cardsPerFile, $totalCardSlots);

            /** Loop through card slots */
            for ($i = $start; $i < $end; $i++) {

                // Add new page
                if (($i % $cardsPerPage) === 0) {
                    $pdf->AddPage();

                    /** -------------------------
                     * DRAW GRID (BEHIND CARDS)
                     * ------------------------- */
                    for ($col = 1; $col < $colsPerPage; $col++) {
                        $xLine = $xStart + $col * $cardWidthPt - 0.5;
                        $pdf->SetFillColor(0, 0, 0);
                        $pdf->Rect($xLine, 0, 1, $pageHeightPt, 'F');
                    }

                    for ($row = 1; $row < $rowsPerPage; $row++) {
                        $yLine = $yStart + $row * $cardHeightPt - 0.5;
                        $pdf->Rect(0, $yLine, $pageWidthPt, 1, 'F');
                    }

                    // Outer edges
                    $pdf->Rect(0, 7 * 2.83465, $pageWidthPt, 1, 'F');
                    $pdf->Rect(0, $pageHeightPt - 7 * 2.83465, $pageWidthPt, 1, 'F');
                    $pdf->Rect(7 * 2.83465, 0, 1, $pageHeightPt, 'F');
                    $pdf->Rect($pageWidthPt - 7 * 2.83465, 0, 1, $pageHeightPt, 'F');
                }

                /** Determine card position */
                $row = floor(($i % $cardsPerPage) / $colsPerPage);
                $col = ($i % $cardsPerPage) % $colsPerPage;
                $xCard = $xStart + $col * $cardWidthPt;
                $yCard = $yStart + $row * $cardHeightPt;

                /** If URL exists → draw real card + QR */
                if (isset($urls[$cardNumber])) {
                    $pdf->useTemplate($tplId, $xCard, $yCard, $cardWidthPt, $cardHeightPt);

                    try {
                        $tempQR = tempnam(sys_get_temp_dir(), 'qr') . ".png";

                        $qr = new QRCode(new QROptions([
                            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                            'eccLevel' => QRCode::ECC_M,
                            'scale' => 20,
                            'quietzoneSize' => 0,
                            'imageTransparent' => true,
                        ]));

                        $qr->render($urls[$cardNumber], $tempQR);

                        // QR placement
                        $qrWidthPt = 46 * 2.83465;
                        $qrHeightPt = 46 * 2.83465;
                        $xOffsetPt = (($cardWidthMM - 46) / 2) * 2.83465;
                        $yOffsetPt = ($cardHeightMM - 42 - 46) * 2.83465;

                        $pdf->Image(
                            $tempQR,
                            $xCard + $xOffsetPt,
                            $yCard + $yOffsetPt,
                            $qrWidthPt,
                            $qrHeightPt,
                            'PNG'
                        );

                        @unlink($tempQR);

                    } catch (\Exception $e) {
                        \Log::error("QR failed: " . $e->getMessage());
                    }
                } else {
                    /** -------------------------
                     * NO URL → PLACE WHITE CARD
                     * ------------------------- */
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->Rect($xCard, $yCard, $cardWidthPt, $cardHeightPt, 'F');
                }

                $cardNumber++;
            }

            /** Save PDF file */
            $fileName = "{$originalName} Part " . ($part + 1) . ".pdf";
            $fullPath = $saveDir . '/' . $fileName;

            $pdf->Output($fullPath, 'F');

            $generatedFiles[] = asset("storage/pdfs/" . rawurlencode($fileName));
        }

        return response()->json([
            'success' => true,
            'pdf_links' => $generatedFiles
        ]);
    }


    public function download($fileName)
    {
        // Decode URL-encoded names (e.g. "%20" → space)
        $decodedName = urldecode($fileName);

        $filePath = storage_path("app/public/pdfs/" . $decodedName);

        if (!file_exists($filePath)) {
            abort(404, "File not found");
        }

        return response()->download($filePath, $decodedName);
    }
}
