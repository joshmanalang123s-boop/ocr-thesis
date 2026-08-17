<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OcrController extends Controller
{
    /**
     * Display the OCR upload form.
     */
    public function index()
    {
        return view('ocr.index');
    }

    /**
     * Process uploaded image for OCR.
     */
    public function process(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('ocr', $filename, 'public');

            // Placeholder for OCR processing
            // In a real implementation, you would use a library like:
            // - Tesseract OCR
            // - Google Cloud Vision
            // - Azure Computer Vision
            // - AWS Textract

            $extractedText = $this->mockOcrProcessing($file);

            return view('ocr.result', [
                'imagePath' => asset('storage/' . $path),
                'extractedText' => $extractedText,
                'fileName' => $file->getClientOriginalName(),
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error processing image: ' . $e->getMessage());
        }
    }

    /**
     * Mock OCR processing - replace with real OCR library
     */
    private function mockOcrProcessing($file)
    {
        return 'Sample extracted text from image. ' .
               'Replace this with actual OCR processing using Tesseract, Google Vision, or similar. ' .
               'The extracted text would appear here with proper formatting and confidence levels.';
    }

    /**
     * Download extracted text as file.
     */
    public function download(Request $request)
    {
        $text = $request->input('text', '');
        $filename = $request->input('filename', 'extracted');

        return response()->download(
            tap(storage_path('temp/' . time() . '.txt'), function ($path) use ($text) {
                file_put_contents($path, $text);
            }),
            $filename . '.txt',
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * Show extraction gallery/history.
     */
    public function history()
    {
        return view('ocr.history');
    }
}
