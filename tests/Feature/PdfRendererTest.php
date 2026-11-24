<?php

namespace Tests\Feature;

use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mpdf\Cache;
use Mpdf\Fonts\FontCache;
use Mpdf\TTFontFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfRendererTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_pdf_has_a5_pages_localized_metadata_and_draft_watermark(string $locale): void
    {
        $procedure = Procedure::factory()->withContent()->create([
            'name' => ['et' => 'Hamba eemaldamine', 'en' => 'Tooth extraction'],
        ]);
        app(Settings::class)->set('clinic.name', ['et' => 'Testkliinik', 'en' => 'Test clinic']);
        $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, null);
        $pdf = app(PdfRenderer::class)->render($doc);

        $this->assertStringStartsWith('%PDF', $pdf);
        preg_match_all('/\/MediaBox\s*\[\s*0\s+0\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $boxes, PREG_SET_ORDER);
        // mPDF also writes the inherited MediaBox on the /Pages parent.
        $this->assertNotEmpty($boxes);
        $this->assertStringContainsString('/Count 2', $pdf);
        foreach ($boxes as $box) {
            $this->assertEqualsWithDelta(148 * 72 / 25.4, (float) $box[1], 0.02);
            $this->assertEqualsWithDelta(210 * 72 / 25.4, (float) $box[2], 0.02);
        }
        $title = __('pdf.metadata_title', ['procedures' => $procedure->getTranslation('name', $locale)], $locale);
        $this->assertStringContainsString(mb_convert_encoding($title, 'UTF-16BE', 'UTF-8'), $pdf);
        $this->assertStringContainsString(mb_convert_encoding(app(Settings::class)->localized('clinic.name', $locale), 'UTF-16BE', 'UTF-8'), $pdf);
        $this->assertStringContainsString('/Lang ('.$locale.')', $pdf);
        $this->assertMatchesRegularExpression('/\/ca 0\.06\b/', $pdf);
        $this->assertDoesNotMatchRegularExpression('/\/ca 0\.(?:15|2)\b/', $pdf);
        $this->assertMatchesRegularExpression('/\/BaseFont \/[A-Z]+\+Nunito-ExtraBold/', $pdf);

        $procedure->update(['status' => ProcedureStatus::Published]);
        $published = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, null);
        $this->assertDoesNotMatchRegularExpression('/\/ca 0\.06\b/', app(PdfRenderer::class)->render($published));
        config(['leaflet.draft_watermark' => false]);
        $this->assertDoesNotMatchRegularExpression('/\/ca 0\.06\b/', app(PdfRenderer::class)->render($doc));
    }

    public function test_print_flag_uses_the_specified_sheet(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        $pdf = app(PdfRenderer::class)->render($doc, true);
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[0 0 476\.22\d* 651\.96\d*\]/', $pdf);
    }

    public function test_every_static_font_contains_all_required_glyphs(): void
    {
        $reader = new TTFontFile(new FontCache(new Cache(config('leaflet.pdf.tempDir'))), 'win');
        $glyphs = mb_str_split('Jäätisetõrjuja šokolaad ŽÕÄÖÜ – „õige“ € · õäöüšž ÕÄÖÜŠŽ — “”');
        $files = glob(resource_path('pdf/fonts/*.ttf'));
        $this->assertCount(6, $files);
        $this->assertLessThan(3 * 1024 * 1024, array_sum(array_map(filesize(...), $files)));
        foreach ($files as $file) {
            $map = $reader->getCTG($file);
            $this->assertArrayNotHasKey('fvar', $reader->tables, basename($file).' must be static');
            foreach ($glyphs as $glyph) {
                $this->assertGreaterThan(0, $map[mb_ord($glyph)] ?? 0, basename($file).' missing '.$glyph);
            }
        }
    }

    public function test_cover_has_no_footer_and_continuation_pages_keep_the_running_footer(): void
    {
        $executable = (new ExecutableFinder)->find('pdftotext');
        if ($executable === null) {
            $this->markTestSkipped('Poppler pdftotext is unavailable.');
        }
        $procedure = Procedure::factory()->withContent()->create([
            'summary' => ['et' => str_repeat('Jäätisetõrjuja šokolaad ŽÕÄÖÜ – „õige“ € · ', 180)],
        ]);
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        $process = new Process([$executable, '-layout', '-', '-']);
        $process->setInput(app(PdfRenderer::class)->render($doc));
        $process->mustRun();
        $pages = explode("\f", trim($process->getOutput(), "\f\r\n "));
        $this->assertGreaterThan(2, count($pages));
        $disclaimer = app(Settings::class)->localized('leaflet.disclaimer', 'et');
        $this->assertStringNotContainsString($disclaimer, $pages[0]);
        $this->assertDoesNotMatchRegularExpression('/1\s*\/\s*'.count($pages).'\b/', $pages[0]);
        foreach (array_slice($pages, 1, null, true) as $index => $page) {
            $this->assertStringContainsString($disclaimer, $page);
            $this->assertMatchesRegularExpression('/'.($index + 1).'\s*\/\s*'.count($pages).'\b/', $page);
        }
        $this->assertStringContainsString('Jäätisetõrjuja šokolaad ŽÕÄÖÜ – „õige“ € ·', $process->getOutput());
    }
}
