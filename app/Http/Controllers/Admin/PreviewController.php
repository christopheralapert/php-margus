<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeafletFormatRequest;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfFailureResponse;
use App\Services\Leaflet\RendersPdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreviewController extends Controller
{
    public function __construct(private readonly LeafletBuilder $builder, private readonly RendersPdf $renderer, private readonly PdfFailureResponse $pdfFailure) {}

    public function __invoke(LeafletFormatRequest $request, Procedure $procedure): Response|StreamedResponse
    {
        $locale = $request->query('lang');
        $locale = in_array($locale, ['et', 'en'], true) ? $locale : app()->getLocale();
        $doc = $this->builder->build(
            [$procedure->id], $locale, __('admin.procedure.preview_first_name', [], $locale),
            now()->toDateString(), includeInactive: true,
        );

        return $this->pdfFailure->download(
            $this->renderer, $doc, $request->selectedFormat(),
            $request->selectedFormat()->filename($locale, 'preview-'.$procedure->slug.'-'.$locale),
            $request->query('print') === '1', inline: true,
        );
    }
}
