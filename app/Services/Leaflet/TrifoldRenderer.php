<?php

namespace App\Services\Leaflet;

use App\Enums\AdviceSection;
use App\Support\Settings;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Mpdf\WatermarkText;
use RuntimeException;
use setasign\Fpdi\PdfParser\StreamReader;

final class TrifoldRenderer
{
    public function __construct(private readonly PdfRenderer $renderer, private readonly PdfAdvice $advice, private readonly Settings $settings) {}

    public function render(LeafletDocument $doc, bool $printMode = false): string
    {
        $css = file_get_contents(resource_path('pdf/css/trifold.css'));
        $mpdf = $this->sheet(printMode: $printMode);
        $offset = $printMode ? 10 : 0;
        $names = implode(', ', array_map(fn (array $section): string => $section['procedure']->getTranslation('name', $doc->locale), $doc->sections));
        $mpdf->SetTitle(__('pdf.metadata_title', ['procedures' => $names], $doc->locale));
        $mpdf->SetSubject(__('pdf.subject', [], $doc->locale));
        $mpdf->SetAuthor($this->settings->localized('clinic.name', $doc->locale) ?? '');
        $mpdf->SetCreator(config('app.name'));
        $mpdf->default_lang = $doc->locale;
        if (config('leaflet.draft_watermark') && $doc->hasDraft()) {
            $mpdf->SetWatermarkText(new WatermarkText(__('pdf.watermark', [], $doc->locale), color: config('leaflet.colors.muted'), alpha: 0.06, font: 'nunito'));
            $mpdf->showWatermarkText = true;
        }
        $sheetCss = $css.($printMode ? "\n@page { size: 297mm 210mm; sheet-size: 317mm 230mm; marks: crop; }" : '');
        $mpdf->WriteHTML($sheetCss, HTMLParserMode::HEADER_CSS);
        $panels = $this->balanceTopics($doc, $this->panels($doc), $css);
        $padding = config('leaflet.trifold.padding');
        foreach (['outside', 'inside'] as $side => $key) {
            // HTML page creation applies @page sheet-size and crop marks.
            $mpdf->WriteHTML($side === 0 ? '<p style="font-size: 1pt; line-height: 1pt;">&nbsp;</p>' : '<pagebreak />');
            if ($printMode) {
                // mPDF clamps a landscape CSS trim width to the portrait sheet
                // width. Restore the symmetric 10 mm surround for crop marks
                // and PDF boxes; panel coordinates stay in physical sheet units.
                $mpdf->page_box['outer_width_LR'] = $offset;
                $mpdf->page_box['outer_width_TB'] = $offset;
                $mpdf->pageDim[$mpdf->page]['outer_width_LR'] = $offset;
                $mpdf->pageDim[$mpdf->page]['outer_width_TB'] = $offset;
                $mpdf->pageDim[$mpdf->page]['bleedMargin'] = config('leaflet.print.bleed');
            }
            $left = 0;
            foreach (config('leaflet.trifold.'.$key) as $index => $width) {
                $kind = array_keys($panels)[$side * 3 + $index];
                $contentWidth = $width - 2 * $padding;
                $panel = $this->fit($doc, $kind, $panels[$kind], $contentWidth, $css);
                // Import the measured normal-flow panel at 1:1 scale. Positioned
                // HTML uses mPDF's header/footer mode, which suppresses margins.
                $mpdf->setSourceFile(StreamReader::createByString($panel));
                $template = $mpdf->importPage(1);
                $mpdf->useTemplate($template, $left + $padding + $offset, $offset, $contentWidth, 210);
                if ($kind === 'cover') {
                    // Top-level decoration: mPDF cannot position nested blocks.
                    $primary = $this->settings->get('brand.colors')['primary'];
                    $bleed = $printMode ? config('leaflet.print.bleed') : 0;
                    $mpdf->SetFillColor($primary);
                    $mpdf->Rect($left + $offset, 198 + $offset, $width + $bleed, 12 + $bleed, 'F');
                    $band = '<div style="position: absolute; top: '.(201 + $offset).'mm; left: '.($left + $offset + $padding).'mm; width: '.$contentWidth.'mm; color: #FFFFFF;">'.e($this->settings->localized('clinic.name', $doc->locale)).'</div>';
                    $mpdf->WriteHTML($band);
                }
                $left += $width;
                if (config('leaflet.trifold.debug_folds') && $index < 2) {
                    $mpdf->SetLineWidth(0.1);
                    $mpdf->Line($left + $offset, $offset, $left + $offset, 210 + $offset);
                }
            }
        }
        if ($mpdf->page !== 2) {
            throw new RuntimeException('Tri-fold layout must have exactly two pages.');
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function sheet(array $overrides = [], bool $printMode = false): Mpdf
    {
        return $this->renderer->createMpdf($printMode, overrides: array_replace([
            'format' => [297, 210], 'margin_top' => 0, 'margin_bottom' => 0,
            'margin_left' => 0, 'margin_right' => 0, 'margin_header' => 0, 'margin_footer' => 0,
            'shrink_tables_to_fit' => 1, 'mirrorMargins' => false,
        ], $overrides));
    }

    private function fit(LeafletDocument $doc, string $kind, array $blocks, int $width, string $css): string
    {
        $truncated = false;
        $followUps = $kind === 'topics' ? $doc->sections : [];
        $artWidth = count($doc->sections) > 1 ? 40 : 60;
        while (true) {
            $probe = $this->panelLayout($doc, $kind, $blocks, $width, $css, $truncated, $followUps, $artWidth);
            $fits = $this->fits($probe);
            if ($fits) {
                return $probe->Output('', Destination::STRING_RETURN);
            }
            unset($probe);
            if ($blocks === []) {
                if ($followUps !== []) {
                    array_pop($followUps);
                    $truncated = true;

                    continue;
                }
                if ($kind === 'cover' && $artWidth > 0) {
                    $artWidth = max(0, $artWidth - 20);

                    continue;
                }
                throw new RuntimeException('Tri-fold fixed content exceeds the panel height.');
            }
            if ($kind === 'topics' && count($blocks) === 1 && $blocks[0]['group'] === 'pain'
                && $blocks[0]['text'] === __('pdf.medication_advice', [], $doc->locale) && $followUps !== []) {
                array_pop($followUps);
                $truncated = true;

                continue;
            }
            // Keep at least the first item from each section for as long as
            // possible. Remove whole lower-priority items, never cut a sentence.
            $counts = array_count_values(array_column($blocks, 'group'));
            $remove = array_key_last($blocks);
            foreach (array_reverse($blocks, true) as $index => $block) {
                // General emergency warnings take priority over the remaining
                // procedure-specific flags. Other lists retain balanced groups.
                if (($kind === 'redflags' && $block['group'] !== 'general')
                    || ($kind !== 'redflags' && $counts[$block['group']] === max($counts))) {
                    $remove = $index;
                    break;
                }
            }
            if ($kind === 'topics' && $blocks[$remove]['group'] === 'pain'
                && $blocks[$remove]['text'] !== __('pdf.medication_advice', [], $doc->locale)) {
                $blocks[$remove]['text'] = __('pdf.medication_advice', [], $doc->locale);
            } else {
                unset($blocks[$remove]);
            }
            $blocks = array_values($blocks);
            $truncated = true;
        }
    }

    private function fits(Mpdf $panel): bool
    {
        return $panel->page === 1 && $panel->y <= 210 - config('leaflet.trifold.padding');
    }

    private function panelLayout(LeafletDocument $doc, string $kind, array $blocks, int $width, string $css, bool $truncated = false, ?array $followUps = null, ?int $artWidth = null): Mpdf
    {
        $settings = $this->settings;
        $followUps ??= $kind === 'topics' ? $doc->sections : [];
        $artWidth ??= count($doc->sections) > 1 ? 40 : 60;
        $html = view('pdf.trifold.panel', compact('doc', 'settings', 'kind', 'blocks', 'truncated', 'followUps', 'artWidth'))->render();
        $padding = config('leaflet.trifold.padding');
        $panel = $this->sheet(['format' => [$width, 210], 'margin_top' => $padding, 'margin_bottom' => $padding]);
        $panel->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        $panel->WriteHTML($html, HTMLParserMode::HTML_BODY);

        return $panel;
    }

    private function balanceTopics(LeafletDocument $doc, array $panels, string $css): array
    {
        $width = config('leaflet.trifold.inside')[2] - 2 * config('leaflet.trifold.padding');
        // Use available space below the cards before omitting a whole topic.
        // This keeps Sleep on the inside sheet in both single-procedure locales.
        while ($panels['topics'] !== [] && ! $this->fits($this->panelLayout($doc, 'topics', $panels['topics'], $width, $css))) {
            $group = $panels['topics'][array_key_last($panels['topics'])]['group'];
            if ($group === 'pain') {
                break;
            }
            $moving = array_values(array_filter($panels['topics'], fn (array $block): bool => $block['group'] === $group));
            $cards = [...$panels['cards'], ...$moving];
            if (! $this->fits($this->panelLayout($doc, 'cards', $cards, $width, $css))) {
                break;
            }
            $panels['cards'] = $cards;
            $panels['topics'] = array_values(array_filter($panels['topics'], fn (array $block): bool => $block['group'] !== $group));
        }

        return $panels;
    }

    private function panels(LeafletDocument $doc): array
    {
        $panels = ['redflags' => [], 'back' => [], 'cover' => [], 'timeline' => [], 'cards' => [], 'topics' => []];
        foreach ($this->advice->redFlags($doc) as $flag) {
            $panels['redflags'][] = ['theme' => $flag['theme'], 'group' => $flag['procedure']?->id ?? 'general', 'heading' => $flag['procedure']?->getTranslation('name', $doc->locale), 'text' => $flag['item']->getTranslation('text', $doc->locale)];
        }
        foreach ($doc->sections as ['procedure' => $procedure, 'theme' => $theme]) {
            foreach ($procedure->timelineSteps as $index => $step) {
                $panels['timeline'][] = ['theme' => $theme, 'number' => $index + 1, 'title' => $step->getTranslation('title', $doc->locale), 'group' => $procedure->id, 'heading' => $procedure->getTranslation('name', $doc->locale), 'label' => $step->getTranslation('marker', $doc->locale), 'text' => $step->getTranslation('body', $doc->locale)];
            }
        }
        foreach (['do', 'dont'] as $section) {
            $items = $doc->general()[$section] ?? collect();
            foreach ($doc->sections as ['procedure' => $procedure]) {
                $items = $items->concat($doc->advice($procedure, AdviceSection::from($section)));
            }
            foreach ($items as $item) {
                if (trim($item->getTranslation('text', $doc->locale)) === '') {
                    continue;
                }
                $panels['cards'][] = [
                    'group' => $section, 'heading' => __('pdf.cards.'.$section, [], $doc->locale),
                    'text' => $item->getTranslation('text', $doc->locale),
                ];
            }
        }

        // Use exactly the same prescribed-medication and shared-item rules as A5.
        $sources = [null, ...array_column($doc->sections, 'procedure')];
        foreach ($sources as $procedure) {
            foreach ($this->advice->topics($procedure, $doc->locale, $doc) as $topic) {
                foreach ($topic['paragraphs'] as $paragraph) {
                    $panels['topics'][] = [
                        'group' => $topic['section'], 'icon' => $topic['icon'],
                        'heading' => __('enums.advice_section.'.$topic['section'], [], $doc->locale),
                        'text' => $paragraph,
                    ];
                }
            }
        }
        $order = array_flip(['pain', 'eating', 'hygiene', 'activity', 'sleep']);
        usort($panels['topics'], fn (array $a, array $b): int => $order[$a['group']] <=> $order[$b['group']]);

        return $panels;
    }
}
