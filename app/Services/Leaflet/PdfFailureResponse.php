<?php

namespace App\Services\Leaflet;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PdfFailureResponse
{
    public function render(Throwable $exception): Response
    {
        // Messages, traces and exception objects may contain patient input.
        Log::error('PDF rendering failed.', ['exception_type' => $exception::class]);

        return response()->view('errors.500', ['pdfFailure' => true], 500, [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
