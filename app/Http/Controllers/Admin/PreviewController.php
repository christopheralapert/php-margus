<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PreviewController extends Controller
{
    public function __construct(private readonly LeafletBuilder $builder, private readonly PdfRenderer $renderer) {}

    public function __invoke(Request $request, Procedure $procedure): Response
    {
        $locale = $request->query('lang');
        $locale = in_array($locale, ['et', 'en'], true) ? $locale : app()->getLocale();
        $doc = $this->builder->build(
            [$procedure->id], $locale, __('admin.procedure.preview_first_name', [], $locale),
            now()->toDateString(), includeInactive: true,
        );

        return response($this->renderer->render($doc, $request->query('print') === '1'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-'.$procedure->slug.'-'.$locale.'.pdf"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
