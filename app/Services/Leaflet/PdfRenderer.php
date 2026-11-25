<?php

namespace App\Services\Leaflet;

use App\Enums\LeafletFormat;
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

    public function render(LeafletDocument $doc, bool $printMode = false, bool $categoryProof = false, LeafletFormat $format = LeafletFormat::A5): string
    {
        if ($format === LeafletFormat::Trifold) {
            return app(TrifoldRenderer::class)->render($doc, $printMode);
        }
        $mpdf = $this->layout($doc, $printMode, $categoryProof);
        // Count real layout pages before serializing. Only print booklets needing
        // padding incur a second layout; the final back page stays last.
        $notesPages = $printMode && ! $categoryProof ? (4 - $mpdf->page % 4) % 4 : 0;
        if ($notesPages > 0) {
            unset($mpdf);
            $mpdf = $this->layout($doc, $printMode, $categoryProof, $notesPages);
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function layout(LeafletDocument $doc, bool $printMode, bool $categoryProof, int $notesPages = 0): Mpdf
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
        $mpdf->WriteHTML($this->views->make('pdf.a5.document', compact('doc', 'settings', 'printMode', 'categoryProof', 'notesPages'))->render(), HTMLParserMode::HTML_BODY);

        return $mpdf;
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

    public function blockSpansPages(string $html): bool
    {
        // Use the actual fonts and mPDF layout rather than guessing from item
        // counts. Only cards that fit a page keep the whole panel together;
        // oversized cards keep their header and first two rows instead.
        $mpdf = $this->createMpdf();
        $mpdf->WriteHTML(file_get_contents(resource_path('pdf/css/a5.css')), HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        return $mpdf->page > 1;
    }

    public function createMpdf(bool $printMode = false, array $overrides = []): Mpdf
    {
        $options = config('leaflet.pdf');
        $options['fontDir'] = [...(new ConfigVariables)->getDefaults()['fontDir'], config('leaflet.font_directory')];
        $options['fontdata'] = array_merge((new FontVariables)->getDefaults()['fontdata'], config('leaflet.fonts'));

        if ($printMode) {
            $options['bleedMargin'] = config('leaflet.print.bleed');
            $options['cropMarkMargin'] = config('leaflet.print.crop_mark_margin');
            $options['cropMarkLength'] = config('leaflet.print.crop_mark_length');
            $options['nonPrintMargin'] = config('leaflet.print.non_print_margin');
            $options['mirrorMargins'] = true;
            $options['margin_left'] = config('leaflet.print.margin_inner');
            $options['margin_right'] = config('leaflet.print.margin_outer');
        }

        return new Mpdf(array_replace($options, $overrides));
    }
}
