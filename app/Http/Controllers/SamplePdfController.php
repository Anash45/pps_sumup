<?php

namespace App\Http\Controllers;

use App\Models\SamplePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SamplePdfController extends Controller
{
    /**
     * Show list of sample PDFs and upload page
     */
    public function index()
    {
        $pdfs = SamplePdf::orderBy('created_at', 'desc')->get();
        return inertia('SamplePdfs/Index', [
            'pdfs' => $pdfs
        ]);
    }

    /**
     * Handle PDF upload via Axios
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // 10 MB max
        ]);

        try {
            $file = $request->file('pdf_file');

            // Generate unique file name
            $fileName = Str::slug($request->title) . '-' . time() . '.' . $file->getClientOriginalExtension();

            // Store in storage/app/sample_pdfs
            $filePath = $file->storeAs('sample_pdfs', $fileName);

            // Save in database
            $pdf = SamplePdf::create([
                'title' => $request->title,
                'path' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PDF uploaded successfully!',
                'pdf' => $pdf,
            ]);

        } catch (\Exception $e) {
            \Log::error("Sample PDF upload failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload PDF. Please try again.',
            ], 500);
        }
    }

    /**
     * Optional: Download PDF from private storage
     */
    public function download(SamplePdf $pdf)
    {
        if (Storage::exists($pdf->path)) {
            return Storage::download($pdf->path, $pdf->title . '.pdf');
        }

        return abort(404, 'File not found.');
    }

    public function destroy(SamplePdf $pdf)
    {
        try {
            // Delete file from storage
            if (Storage::exists($pdf->path)) {
                Storage::delete($pdf->path);
            }

            // Delete database record
            $pdf->delete();

            return response()->json([
                'success' => true,
                'message' => 'PDF deleted successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to delete PDF ({$pdf->id}): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete PDF. Please try again.',
            ], 500);
        }
    }
}
