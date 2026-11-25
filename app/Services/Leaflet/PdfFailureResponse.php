<?php

namespace App\Services\Leaflet;

use App\Enums\LeafletFormat;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class PdfFailureResponse
{
    public function download(RendersPdf $renderer, LeafletDocument $doc, LeafletFormat $format, string $filename, bool $printMode = false, bool $inline = false, bool $stream = false): Response|StreamedResponse
    {
        try {
            $bytes = $renderer->render($doc, $printMode, format: $format);
        } catch (Throwable $exception) {
            return $this->render($exception);
        }

        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, private',
        ];
        if ($stream) {
            return response()->streamDownload(function () use ($bytes): void {
                echo $bytes;
            }, $filename, $headers);
        }

        $headers['Content-Disposition'] = ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"';

        return response($bytes, 200, $headers);
    }

    public function render(Throwable $exception): Response
    {
        // Messages, traces and exception objects may contain patient input.
        Log::error('PDF rendering failed.', ['exception_type' => $exception::class]);

        return response()->view('errors.500', ['pdfFailure' => true], 500, [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
