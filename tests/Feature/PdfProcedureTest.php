<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfIcons;
use App\Services\Leaflet\PdfRenderer;
use App\Services\Leaflet\ThemeColor;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfProcedureTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_all_fifteen_seeded_procedures_render_their_translated_headers_and_timeline(string $locale): void
    {
        $this->seed(DatabaseSeeder::class);
        $procedures = Procedure::all();
        $this->assertCount(15, $procedures);
        foreach ($procedures as $procedure) {
            $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, null);
            $html = view('pdf.a5.procedure', ['doc' => $doc, ...$doc->sections[0]])->render();
            $this->assertStringContainsString(e($procedure->getTranslation('name', $locale)), $html);
            $this->assertStringContainsString(e(mb_strtoupper($procedure->category->getTranslation('name', $locale), 'UTF-8')), $html);
            $this->assertStringContainsString(__('pdf.recovery_timeline', [], $locale), $html);
            $this->assertStringContainsString('color: '.$doc->sections[0]['theme']->on().';', $html);
            $xml = new DOMDocument;
            @$xml->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new DOMXPath($xml);
            $markers = $xpath->query('//table[contains(@class,"timeline-markers")]//td');
            $this->assertCount(4, $markers);
            foreach ($procedure->timelineSteps as $index => $step) {
                $this->assertSame($step->getTranslation('marker', $locale), trim($markers->item($index)->textContent));
                $phase = $xpath->query('//td[@class="timeline-phase"]')->item($index);
                $label = $xpath->query('.//div[@class="label"]', $phase)->item(0);
                $expected = mb_strtoupper($step->getTranslation('marker', $locale), 'UTF-8');
                $this->assertSame($expected, str_replace("\u{00A0}", ' ', trim($label->textContent)));
                $this->assertSame(preg_replace('/ (?=\d)|(?<=\d\.) /u', "\u{00A0}", $expected), trim($label->textContent));
                $this->assertStringNotContainsString('image/png', $xml->saveHTML($phase));
                $this->assertStringContainsString(e($step->getTranslation('title', $locale)), $html);
                $this->assertStringContainsString(e($step->getTranslation('body', $locale)), $html);
            }
            $this->assertCount(4, $xpath->query('//table[@class="timeline-step"]'));
            $this->assertCount(1, $xpath->query('//div[@class="keep"][h2 and table[contains(@class,"timeline-step")]]'));
        }
    }

    public function test_light_bases_use_ink_and_dark_bases_use_white(): void
    {
        foreach (['#2BB3A3', '#F08A24'] as $base) {
            $this->assertSame('#1F2937', (new ThemeColor($base))->on());
        }
        $this->assertSame('#FFFFFF', (new ThemeColor('#2E6FD8'))->on());
        $this->assertSame('#FFFFFF', (new ThemeColor('#A23E9C'))->on());
    }

    public function test_ruler_has_four_crisp_path_numbers_and_a_centred_connecting_rule(): void
    {
        $theme = new ThemeColor('#2BB3A3');
        $html = app(PdfIcons::class)->timelineRuler($theme);
        preg_match('/base64,([^"]+)/', $html, $match);
        $xml = new DOMDocument;
        $xml->loadXML(base64_decode($match[1]), LIBXML_NONET);
        $this->assertCount(4, $xml->getElementsByTagName('circle'));
        $this->assertCount(4, $xml->getElementsByTagName('path'));
        $this->assertCount(0, $xml->getElementsByTagName('text'));
        foreach ($xml->getElementsByTagName('circle') as $index => $circle) {
            $this->assertSame((string) (15 + $index * 30), $circle->getAttribute('cx'));
            $this->assertSame('3', $circle->getAttribute('cy'));
            $this->assertSame('3', $circle->getAttribute('r'));
        }
        $rule = $xml->getElementsByTagName('rect')->item(0);
        $this->assertEqualsWithDelta(3, (float) $rule->getAttribute('y') + (float) $rule->getAttribute('height') / 2, 0.001);
    }

    #[DataProvider('locales')]
    public function test_procedure_page_contains_all_four_markers_as_extractable_text(string $locale): void
    {
        $textTool = (new ExecutableFinder)->find('pdftotext');
        if (! $textTool) {
            $this->markTestSkipped('pdftotext is unavailable.');
        }

        $this->seed(DatabaseSeeder::class);
        $procedure = Procedure::where('slug', 'tooth-extraction')->firstOrFail();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, null);
        $text = new Process([$textTool, '-f', '2', '-l', '2', '-layout', '-', '-']);
        $text->setInput(app(PdfRenderer::class)->render($doc));
        $text->mustRun();
        $normalize = static fn (string $value): string => preg_replace('/\s+/u', ' ', mb_strtoupper($value, 'UTF-8'));
        $page = $normalize($text->getOutput());

        $this->assertCount(4, $procedure->timelineSteps);
        foreach ($procedure->timelineSteps as $step) {
            // Both the approved ruler and the vertical step must carry real text.
            $this->assertGreaterThanOrEqual(2, substr_count($page, $normalize($step->getTranslation('marker', $locale))));
        }
    }

    public function test_long_sections_keep_their_strip_on_every_continuation_page(): void
    {
        $finder = new ExecutableFinder;
        $textTool = $finder->find('pdftotext');
        $imageTool = $finder->find('pdftoppm');
        if (! $textTool || ! $imageTool) {
            $this->markTestSkipped('Poppler is unavailable.');
        }
        config(['leaflet.draft_watermark' => false]);
        $procedures = collect(['#2BB3A3', '#F08A24'])->map(fn (string $color, int $index) => Procedure::factory()->withContent()->create([
            'color' => $color,
            'name' => ['en' => 'Section '.($index + 1)],
            'summary' => ['en' => str_repeat('Recovery advice for this procedure. ', 170)],
        ]));
        $doc = app(LeafletBuilder::class)->build($procedures->pluck('id')->all(), 'en', null, null);
        $pdf = app(PdfRenderer::class)->render($doc);
        $text = new Process([$textTool, '-layout', '-', '-']);
        $text->setInput($pdf);
        $text->mustRun();
        $pages = explode("\f", trim($text->getOutput(), "\f\r\n "));
        $color = null;
        $counts = ['#2BB3A3' => 0, '#F08A24' => 0];
        foreach (array_slice($pages, 1, null, true) as $index => $page) {
            foreach ($procedures as $procedure) {
                if (str_contains($page, $procedure->getTranslation('name', 'en'))) {
                    $color = $procedure->color;
                }
            }
            $this->assertNotNull($color);
            $image = new Process([$imageTool, '-f', (string) ($index + 1), '-l', (string) ($index + 1), '-singlefile', '-r', '30', '-png', '-']);
            $image->setInput($pdf);
            $image->mustRun();
            $png = imagecreatefromstring($image->getOutput());
            $this->assertSame(hexdec(substr($color, 1)), imagecolorat($png, 10, 1) & 0xFFFFFF);
            imagedestroy($png);
            $counts[$color]++;
        }
        foreach ($counts as $count) {
            $this->assertGreaterThan(1, $count);
        }
    }
}
