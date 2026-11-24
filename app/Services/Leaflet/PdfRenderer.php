<?php

namespace App\Services\Leaflet;

use Illuminate\Contracts\View\Factory;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

final class PdfRenderer
{
    public const DEFAULT_FONT = 'dejavusans';

    public function __construct(private readonly Factory $views) {}

    public function render(LeafletDocument $doc): string
    {
        $mpdf = new Mpdf([
            'format' => 'A5',
            'orientation' => 'P',
            'default_font' => self::DEFAULT_FONT,
            'tempDir' => storage_path('framework/cache/mpdf'),
        ]);
        $mpdf->SetTitle($doc->title());
        $mpdf->SetCreator(config('app.name'));
        $mpdf->default_lang = $doc->locale;
        $mpdf->WriteHTML($this->views->make('pdf.minimal', compact('doc'))->render());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
