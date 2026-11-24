<?php

namespace App\Services\Leaflet;

use App\Support\Settings;
use Illuminate\Contracts\View\Factory;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Mpdf\WatermarkText;

final class PdfRenderer
{
    public const DEFAULT_FONT = 'sourcesans3';

    public function __construct(private readonly Factory $views, private readonly Settings $settings) {}

    public function render(LeafletDocument $doc, bool $printMode = false, bool $categoryProof = false): string
    {
        $mpdf = $this->createMpdf($printMode);
        $names = implode(', ', array_map(fn (array $section): string => $section['procedure']->getTranslation('name', $doc->locale), $doc->sections));
        $mpdf->SetTitle(__('pdf.metadata_title', ['procedures' => $names], $doc->locale));
        $mpdf->SetAuthor($this->settings->localized('clinic.name', $doc->locale) ?? '');
        $mpdf->SetSubject(__('pdf.subject', [], $doc->locale));
        $mpdf->SetCreator(config('app.name'));
        // mPDF 8.3 has no SetLang: default_lang writes the PDF catalogue /Lang.
        $mpdf->default_lang = $doc->locale;
        $mpdf->SetDirectionality('ltr');

        if (config('leaflet.draft_watermark') && $doc->hasDraft()) {
            // Keep the specified alpha; muted ink softens the large diagonal text.
            $mpdf->SetWatermarkText(new WatermarkText(
                __('pdf.watermark', [], $doc->locale),
                color: config('leaflet.colors.muted'), alpha: 0.06, font: 'nunito',
            ));
            $mpdf->showWatermarkText = true;
        }

        $settings = $this->settings;
        $css = file_get_contents(resource_path('pdf/css/a5.css'));
        $css .= $this->views->make('pdf.a5.styles', compact('doc', 'settings'))->render();
        if ($printMode) {
            [$width, $height] = config('leaflet.print.sheet');
            $css .= "\n@page { sheet-size: {$width}mm {$height}mm; marks: crop;"
                .' margin-left: '.config('leaflet.print.margin_inner').'mm; margin-right: '.config('leaflet.print.margin_outer').'mm;'
                .' margin-top: '.config('leaflet.pdf.margin_top').'mm; margin-bottom: '.config('leaflet.pdf.margin_bottom').'mm;'
                .' margin-header: '.config('leaflet.pdf.margin_header').'mm; margin-footer: '.config('leaflet.pdf.margin_footer').'mm; }';
        }

        $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($this->views->make('pdf.a5.document', compact('doc', 'settings', 'printMode', 'categoryProof'))->render(), HTMLParserMode::HTML_BODY);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    public function renderIconSheet(): string
    {
        $mpdf = $this->createMpdf();
        $mpdf->SetTitle(__('pdf.icons_title'));
        $mpdf->default_lang = app()->getLocale();
        $mpdf->WriteHTML(file_get_contents(resource_path('pdf/css/a5.css')), HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($this->views->make('pdf.a5.icons', ['settings' => $this->settings])->render(), HTMLParserMode::HTML_BODY);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function createMpdf(bool $printMode = false): Mpdf
    {
        $options = config('leaflet.pdf');
        $options['fontDir'] = [...(new ConfigVariables)->getDefaults()['fontDir'], config('leaflet.font_directory')];
        $options['fontdata'] = array_merge((new FontVariables)->getDefaults()['fontdata'], config('leaflet.fonts'));

        if ($printMode) {
            $options['bleedMargin'] = config('leaflet.print.bleed');
            $options['mirrorMargins'] = true;
            $options['margin_left'] = config('leaflet.print.margin_inner');
            $options['margin_right'] = config('leaflet.print.margin_outer');
        }

        return new Mpdf($options);
    }
}
