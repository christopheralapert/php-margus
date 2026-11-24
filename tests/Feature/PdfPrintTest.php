<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfPrintTest extends TestCase
{
    use RefreshDatabase;

    public static function booklets(): array
    {
        return [[1, 'et'], [2, 'en'], [3, 'et'], [4, 'en']];
    }

    #[DataProvider('booklets')]
    public function test_padding_uses_actual_layout_and_preserves_the_back_page(int $count, string $locale): void
    {
        config(['leaflet.draft_watermark' => false]);
        $procedures = Procedure::factory()->count($count)->withContent()->create();
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), $locale, null, null);
        $renderer = app(PdfRenderer::class);
        $screen = $renderer->render($doc);
        $print = $renderer->render($doc, printMode: true);
        $pages = $this->pageCount($screen);
        $padding = (4 - $pages % 4) % 4;
        $this->assertSame($pages + $padding, $this->pageCount($print));
        $this->assertSame(0, $this->pageCount($print) % 4);
        $this->assertStringContainsString('/TrimBox [0.000 0.000 419.528 595.276]', $screen);
        $this->assertStringContainsString('/MediaBox [0 0 419.528 595.276]', $screen);

        $tool = (new ExecutableFinder)->find('pdftotext');
        if ($tool === null) {
            $this->markTestSkipped('Page counts and boxes passed; pdftotext unavailable for notes content.');
        }
        $extract = function (string $pdf) use ($tool): array {
            $process = new Process([$tool, '-layout', '-', '-']);
            $process->setInput($pdf)->mustRun();

            return explode("\f", rtrim($process->getOutput(), "\f\r\n "));
        };
        $screenPages = $extract($screen);
        $printPages = $extract($print);
        $title = __('pdf.notes', [], $locale);
        $this->assertStringNotContainsString($title, implode('', $screenPages));
        $notes = array_values(array_filter($printPages, fn (string $page): bool => str_contains($page, $title)));
        $this->assertCount($padding, $notes);
        foreach ($notes as $index => $note) {
            $this->assertStringContainsString(app(Settings::class)->localized('leaflet.disclaimer', $locale), $note);
            $this->assertMatchesRegularExpression('/'.($pages + $index).'\s*\/\s*'.count($printPages).'\b/', $note);
        }
        $this->assertStringContainsString(__('pdf.back_emergency', [], $locale), end($printPages));
        $this->assertStringNotContainsString($title, end($printPages));
        $this->assertStringContainsString(__('pdf.qr_caption', [], $locale), end($screenPages));
    }

    public function test_every_print_page_has_sheet_trim_and_bleed_boxes(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        $pdf = app(PdfRenderer::class)->render($doc, printMode: true);
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        $decoded = implode('', array_map(fn (string $stream): string => @gzuncompress($stream) ?: '', $streams[1]));
        // 5 mm crop marks from sheet x=1 to x=6, clear of bleed at x=7.
        // mPDF's default 8 mm nonPrintMargin otherwise reverses these lines.
        $this->assertStringContainsString('2.835 623.622 m 17.008 623.622 l S', $decoded);
        $this->assertStringContainsString('28.346 649.134 m 28.346 634.961 l S', $decoded);
        foreach ([
            'MediaBox' => [0, 0, 168, 230],
            'TrimBox' => [10, 10, 158, 220],
            'BleedBox' => [7, 7, 161, 223],
        ] as $name => $expected) {
            preg_match_all('/\/'.$name.'\s*\[([\d. ]+)\]/', $pdf, $boxes);
            $this->assertGreaterThanOrEqual($this->pageCount($pdf), count($boxes[1]));
            foreach ($boxes[1] as $box) {
                foreach (preg_split('/\s+/', trim($box)) as $coordinate => $points) {
                    $this->assertEqualsWithDelta($expected[$coordinate], (float) $points * 25.4 / 72, 0.01, $name);
                }
            }
        }
        $tool = (new ExecutableFinder)->find('pdfinfo');
        if ($tool !== null) {
            $info = new Process([$tool, '-box', '-']);
            $info->setInput($pdf)->mustRun();
            $this->assertMatchesRegularExpression('/TrimBox:\s+28\.35\s+28\.35\s+447\.87\s+623\.62/', $info->getOutput());
        }
    }

    public function test_notes_margins_are_mirrored_and_lines_are_eight_mm_apart(): void
    {
        $tool = (new ExecutableFinder)->find('pdftotext');
        if ($tool === null) {
            $this->markTestSkipped('pdftotext unavailable.');
        }
        config(['leaflet.draft_watermark' => false]);
        $procedure = Procedure::factory()->withContent()->create();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        $pdf = app(PdfRenderer::class)->render($doc, printMode: true);
        $bounds = new Process([$tool, '-bbox', '-', '-']);
        $bounds->setInput($pdf)->mustRun();
        preg_match_all('/<word xMin="([\d.]+)"[^>]*>Märkmed<\/word>/u', $bounds->getOutput(), $titles);
        $this->assertCount(2, $titles[1]);
        foreach ([22, 26] as $index => $mm) {
            $this->assertEqualsWithDelta($mm, (float) $titles[1][$index] * 25.4 / 72, 0.1);
        }
        // Inspect actual vector line positions, not rasterized text or a CSS assertion.
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        $linePages = 0;
        foreach ($streams[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                continue;
            }
            preg_match_all('/[\d.]+ ([\d.]+) m\s+[\d.]+ \1 l\s+S/', $decoded, $lines);
            $positions = array_values(array_unique($lines[1]));
            $runs = 0;
            for ($i = 1; $i < count($positions); $i++) {
                if (abs(abs((float) $positions[$i] - (float) $positions[$i - 1]) * 25.4 / 72 - 8) < 0.05) {
                    $runs++;
                }
            }
            if ($runs >= 17) {
                $linePages++;
            }
        }
        $this->assertSame(2, $linePages);
    }

    private function pageCount(string $pdf): int
    {
        preg_match('/\/Type \/Pages\s*\/Kids\s*\[[^]]*\]\s*\/Count (\d+)/s', $pdf, $match);
        $this->assertNotEmpty($match);

        return (int) $match[1];
    }
}
