<?php

namespace Tests\Feature;

use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfCoverTest extends TestCase
{
    use RefreshDatabase;

    public static function layouts(): array
    {
        return [
            ['et', 1, 'Mari', '2026-11-24'],
            ['en', 1, 'Mary', '2026-11-24'],
            ['et', 3, null, null],
            ['et', 1, null, null],
        ];
    }

    #[DataProvider('layouts')]
    public function test_cover_spacing_is_balanced_and_content_starts_on_the_next_page(string $locale, int $count, ?string $name, ?string $date): void
    {
        $executable = (new ExecutableFinder)->find('pdftotext');
        if ($executable === null) {
            $this->markTestSkipped('Poppler pdftotext is unavailable.');
        }
        config(['leaflet.draft_watermark' => false]);
        $procedures = Procedure::factory()->withContent()->count($count)->create([
            'name' => ['et' => 'Hamba eemaldamine', 'en' => 'Tooth extraction'],
            'illustration_path' => 'illustrations/dental/tooth-extraction.svg',
        ]);
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), $locale, $name, $date);
        $html = view('pdf.a5.cover', ['doc' => $doc, 'settings' => app(Settings::class), 'printMode' => false])->render();
        preg_match('/top: ([\d.]+)mm;[^>]+height: ([\d.]+)mm; border-radius/', $html, $art);
        preg_match('/class="cover-copy" style="top: ([\d.]+)mm/', $html, $copy);
        $artBottom = (float) $art[1] + (float) $art[2];
        $this->assertGreaterThanOrEqual(8, (float) $copy[1] - $artBottom);

        $pdf = app(PdfRenderer::class)->render($doc);
        preg_match('/\/Type \/Pages\s*\/Kids\s*\[[^]]*\]\s*\/Count (\d+)/s', $pdf, $pageCount);
        // Three content pages per procedure, plus cover, red flags and back. The
        // three-procedure fixture continues its follow-ups onto another page.
        $this->assertSame(3 * $count + 3 + ($count > 1 ? 1 : 0), (int) ($pageCount[1] ?? 0));
        $process = new Process([$executable, '-f', '1', '-l', '1', '-bbox', '-', '-']);
        $process->setInput($pdf);
        $process->mustRun();
        $xml = new DOMDocument;
        $xml->loadXML($process->getOutput(), LIBXML_NONET);
        $words = (new DOMXPath($xml))->query('//*[local-name()="word"]');
        $mm = 25.4 / 72;
        $titleWord = explode(' ', __('pdf.title', [], $locale))[0];
        $textBottom = 0;
        $foundTitle = false;
        foreach ($words as $word) {
            $top = (float) $word->getAttribute('yMin') * $mm;
            $bottom = (float) $word->getAttribute('yMax') * $mm;
            if ($word->textContent === $titleWord) {
                $foundTitle = true;
                // Poppler includes the font's ascender space above the visible letters.
                $this->assertGreaterThanOrEqual($artBottom + 6, $top);
            }
            if ($top > $artBottom && $bottom < 190) {
                $textBottom = max($textBottom, $bottom);
            }
        }
        $this->assertTrue($foundTitle);
        // Include the draft pill's 2mm bottom padding; allow mPDF font metrics.
        $this->assertEqualsWithDelta((float) $art[1] - 23, 198 - ($textBottom + 2), 8);
        $this->assertLessThan(188, $textBottom + 2);
        $this->assertStringNotContainsString($procedures->first()->getTranslation('summary', $locale), strip_tags($process->getOutput()));
    }

    public static function details(): array
    {
        return [
            ['et', 'Mari', '2026-11-24', 'Mari jaoks · 24.11.2026'],
            ['en', 'Mary', '2026-11-24', 'For Mary · 24 Nov 2026'],
            ['et', 'Mari', null, 'Mari jaoks'],
            ['en', null, '2026-11-24', '24 Nov 2026'],
            ['et', null, null, null],
        ];
    }

    #[DataProvider('details')]
    public function test_cover_personal_details_only_include_given_parts(string $locale, ?string $name, ?string $date, ?string $expected): void
    {
        $procedure = Procedure::factory()->create();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, $name, $date);
        $html = view('pdf.a5.cover', ['doc' => $doc, 'settings' => app(Settings::class), 'printMode' => false])->render();
        $text = preg_replace('/\s+/u', ' ', strip_tags($html));

        if ($expected === null) {
            $this->assertStringNotContainsString('cover-sub', $html);
        } else {
            $this->assertStringContainsString($expected, $text);
            if ($name === null || $date === null) {
                $this->assertStringNotContainsString('·', $text);
            }
        }
        $this->assertStringContainsString(__('pdf.title', [], $locale), $html);
        $this->assertStringNotContainsString('Â', $html);
    }

    public function test_multi_cover_limits_art_to_three_keeps_all_names_and_any_draft_notice(): void
    {
        $procedures = Procedure::factory()->published()->count(4)->create([
            'illustration_path' => 'illustrations/dental/tooth-extraction.svg',
        ]);
        $procedures->last()->update(['status' => ProcedureStatus::Draft]);
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), 'et', null, null);
        $html = view('pdf.a5.cover', ['doc' => $doc, 'settings' => app(Settings::class), 'printMode' => false])->render();

        $this->assertSame(3, substr_count($html, 'width="38mm"'));
        foreach ($procedures as $procedure) {
            $this->assertStringContainsString($procedure->getTranslation('name', 'et'), $html);
        }
        $this->assertStringContainsString(__('pdf.draft_notice'), $html);
        $this->assertStringStartsWith('%PDF', app(PdfRenderer::class)->render($doc));
    }

    public function test_published_single_cover_uses_cover_art_override_and_escapes_patient_name(): void
    {
        $procedure = Procedure::factory()->published()->create([
            'cover_illustration_path' => 'illustrations/ent/tonsillectomy.svg',
        ]);
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'en', '<script>Mary</script>', null);
        $html = view('pdf.a5.cover', ['doc' => $doc, 'settings' => app(Settings::class), 'printMode' => false])->render();
        $this->assertStringContainsString(public_path($procedure->cover_illustration_path), $html);
        $this->assertStringContainsString('width="90mm"', $html);
        $this->assertStringNotContainsString('draft-notice', $html);
        $this->assertStringContainsString('&lt;script&gt;Mary&lt;/script&gt;', $html);
        $this->assertStringStartsWith('%PDF', app(PdfRenderer::class)->render($doc));
    }
}
