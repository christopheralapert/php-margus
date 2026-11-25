<?php

namespace Tests\Feature;

use App\Enums\LeafletFormat;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\OnlineLeafletUrl;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfTrifoldTest extends TestCase
{
    use RefreshDatabase;

    public static function samples(): array
    {
        return [['et', 1], ['en', 1], ['et', 3]];
    }

    #[DataProvider('samples')]
    public function test_two_landscape_pages_have_readable_text_within_panel_safe_zones(string $locale, int $count): void
    {
        $this->seed();
        config(['leaflet.draft_watermark' => false]);
        $slugs = array_slice(['tooth-extraction', 'mole-removal', 'knee-arthroscopy'], 0, $count);
        $ids = array_map(fn (string $slug): int => Procedure::where('slug', $slug)->sole()->id, $slugs);
        $doc = app(LeafletBuilder::class)->build($ids, $locale, 'Mari', '2026-11-24');
        $pdf = app(PdfRenderer::class)->render($doc, format: LeafletFormat::Trifold);
        $this->assertSheet($pdf);
        $text = $this->extract($pdf);
        foreach ([app(Settings::class)->get('clinic.phone'), '112', $doc->sections[0]['procedure']->getTranslation('name', $locale), __('pdf.cards.do', [], $locale), __('pdf.cards.dont', [], $locale), __('pdf.follow_up', [], $locale)] as $expected) {
            $this->assertStringContainsString($expected, $text);
        }
        if ($count === 1) {
            $raw = preg_replace('/[\s\p{Cf}]+/u', '', $this->extract($pdf, '-raw'));
            $this->assertSame(1, substr_count($raw, preg_replace('/\s+/u', '', __('pdf.medication_prescribed_phrase', [], $locale))));
            $this->assertStringContainsString(__('enums.advice_section.sleep', [], $locale), $raw);
            $this->assertStringNotContainsString(preg_replace('/\s+/u', '', __('pdf.full_guide', [], $locale)), $raw);
        }
        if ($count === 3) {
            $this->assertStringContainsString(__('pdf.full_guide', [], $locale), $text);
            $this->assertStringContainsString(preg_replace('~^https?://~', '', (new OnlineLeafletUrl($doc))->onlineUrl()), preg_replace('/[\s\p{Cf}]+/u', '', $text));
        }
        $xml = simplexml_load_string($this->extract($pdf, '-bbox'));
        $xml->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');
        foreach ($xml->xpath('//x:page') as $side => $page) {
            $page->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');
            $edges = $side === 0 ? [0, 100, 200, 297] : [0, 97, 197, 297];
            foreach ($page->xpath('.//x:word') as $word) {
                $x1 = (float) $word['xMin'] * 25.4 / 72;
                $x2 = (float) $word['xMax'] * 25.4 / 72;
                $this->assertGreaterThanOrEqual(9, (float) $word['yMin'] * 25.4 / 72, (string) $word);
                // The cover band has a 5 mm trim-safe text inset.
                $this->assertLessThanOrEqual(206, (float) $word['yMax'] * 25.4 / 72, (string) $word);
                $fits = false;
                for ($panel = 0; $panel < 3; $panel++) {
                    $fits = $fits || ($x1 >= $edges[$panel] + 9 && $x2 <= $edges[$panel + 1] - 9);
                }
                $this->assertTrue($fits, 'Text crosses panel safe zone: '.$word);
            }
        }
    }

    public function test_long_advice_and_followup_use_full_guide_without_adding_pages(): void
    {
        $procedure = Procedure::factory()->withContent()->create([
            'follow_up' => ['et' => str_repeat('Pikk järelkontrolli juhis. ', 300)],
        ]);
        $procedure->adviceItems()->update(['text' => ['et' => str_repeat('Pikk soovitus. ', 300)]]);
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        $pdf = app(PdfRenderer::class)->render($doc, format: LeafletFormat::Trifold);
        $this->assertSheet($pdf);
        $this->assertStringContainsString('Täielik juhend:', $this->extract($pdf));
        $this->assertStringContainsString(preg_replace('/\s+/u', '', __('pdf.medication_advice', [], 'et')), preg_replace('/[\s\p{Cf}]+/u', '', $this->extract($pdf, '-raw')));
    }

    public function test_print_sheet_has_crop_marks_and_three_millimetres_of_cover_band_bleed(): void
    {
        $this->seed();
        config(['leaflet.draft_watermark' => false]);
        $procedure = Procedure::where('slug', 'tooth-extraction')->sole();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        $pdf = app(PdfRenderer::class)->render($doc, true, format: LeafletFormat::Trifold);
        preg_match_all('/\/Type \/Page\b.*?\/MediaBox\s*\[0 0 ([\d.]+) ([\d.]+)\]/s', $pdf, $boxes, PREG_SET_ORDER);
        $this->assertCount(2, $boxes);
        foreach ($boxes as $box) {
            $this->assertEqualsWithDelta(317 * 72 / 25.4, (float) $box[1], 0.1);
            $this->assertEqualsWithDelta(230 * 72 / 25.4, (float) $box[2], 0.1);
        }
        foreach (['TrimBox' => [10, 10, 307, 220], 'BleedBox' => [7, 7, 310, 223]] as $name => $expected) {
            preg_match('/\/'.$name.'\s*\[([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+)\]/', $pdf, $box);
            $this->assertCount(5, $box, $name);
            foreach ($expected as $index => $mm) {
                $this->assertEqualsWithDelta($mm * 72 / 25.4, (float) $box[$index + 1], 0.1, $name);
            }
        }
    }

    public function test_wizard_validates_format_and_exposes_the_second_download(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $this->withSession(['wizard.procedures' => [$procedure->id]])
            ->get('/et/leaflet/preview')->assertOk()->assertSee('Lae alla A4 kolmikvoldik');
        foreach (['invalid', ['trifold'], null] as $invalid) {
            $this->post('/et/leaflet/pdf', ['format' => $invalid])->assertSessionHasErrors('format');
        }
        $response = $this->post('/et/leaflet/pdf', ['format' => 'trifold'])->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="ravijargne-juhend-kolmikvoldik.pdf"')
            ->assertSessionMissing('wizard');
        $this->assertSheet($response->getContent());
    }

    public function test_saved_format_is_preserved_and_used_for_regeneration(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $this->actingAs(User::factory()->create())
            ->withSession(['wizard.procedures' => [$procedure->id]])
            ->post('/en/account/leaflets', ['format' => 'trifold'])->assertRedirect('/en/account/leaflets');
        $saved = SavedLeaflet::sole();
        $this->assertSame(LeafletFormat::Trifold, $saved->format);
        $response = $this->get('/en/account/leaflets/'.$saved->uuid.'/pdf')->assertOk();
        $this->assertStringContainsString('aftercare-guide-trifold.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSheet($response->streamedContent());
    }

    public function test_admin_can_preview_trifold_and_rejects_unknown_format(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $url = '/en/admin/procedures/'.$procedure->id.'/preview.pdf';
        $this->get($url.'?format=invalid')->assertSessionHasErrors('format');
        $this->assertSheet($this->get($url.'?format=trifold')->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="preview-'.$procedure->slug.'-en-trifold.pdf"')->getContent());
    }

    private function assertSheet(string $pdf): void
    {
        preg_match('/\/Type \/Pages\s*\/Kids\s*\[[^]]*\]\s*\/Count (\d+)/s', $pdf, $count);
        $this->assertSame(2, (int) ($count[1] ?? 0));
        preg_match_all('/\/MediaBox\s*\[0 0 ([\d.]+) ([\d.]+)\]/', $pdf, $boxes, PREG_SET_ORDER);
        $this->assertNotEmpty($boxes);
        foreach ($boxes as $box) {
            $this->assertEqualsWithDelta(842, (float) $box[1], 1);
            $this->assertEqualsWithDelta(595, (float) $box[2], 1);
        }
        // Check the PDF's actual text operators, not only the declared CSS:
        // mPDF must never shrink a panel to make it fit. The sole 1 pt font is
        // a nonprinting whitespace anchor before the positioned panels.
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
        $sizes = [];
        foreach ($streams[1] as $stream) {
            $content = @gzuncompress($stream);
            if ($content === false) {
                continue;
            }
            preg_match_all('/\/F\d+\s+([\d.]+)\s+Tf/', $content, $fonts);
            foreach ($fonts[1] as $size) {
                if (! in_array((float) $size, [1.0, 7.5], true)) {
                    $this->assertGreaterThanOrEqual(10.5, (float) $size);
                    $sizes[] = (float) $size;
                }
            }
        }
        $this->assertContains(10.5, $sizes);
        $this->assertContains(16.0, $sizes);
    }

    private function extract(string $pdf, string $mode = '-layout'): string
    {
        $process = new Process(['pdftotext', $mode, '-', '-']);
        $process->setInput($pdf)->mustRun();

        return $process->getOutput();
    }
}
