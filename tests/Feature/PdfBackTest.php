<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\OnlineLeafletUrl;
use App\Services\Leaflet\PdfRenderer;
use App\Services\Leaflet\QrCodeFactory;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfBackTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et', '24.11.2026'], ['en', '24 Nov 2026']];
    }

    #[DataProvider('locales')]
    public function test_back_uses_live_settings_localized_generation_date_and_nonpersonal_qr(string $locale, string $date): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        config(['leaflet.content_version' => 'v7']);
        $procedures = Procedure::factory()->count(2)->create();
        $doc = app(LeafletBuilder::class)->build($procedures->reverse()->modelKeys(), $locale, 'PrivatePatient', '2025-01-13');
        $settings = app(Settings::class);
        $render = fn (): string => view('pdf.a5.back', ['doc' => $doc, 'settings' => $settings, 'printMode' => false])->render();
        $html = $render();
        foreach (['clinic.phone', 'clinic.after_hours_phone', 'clinic.email'] as $key) {
            $this->assertStringContainsString(e($settings->get($key)), $html);
        }
        foreach (['clinic.name', 'clinic.address', 'clinic.hours', 'leaflet.disclaimer'] as $key) {
            $this->assertStringContainsString(e($settings->localized($key, $locale)), $html);
        }
        $settings->set('clinic.phone', '+372 555 0123');
        $settings->set('clinic.address', ['et' => 'Uus aadress', 'en' => 'New address']);
        $html = $render();
        $this->assertStringContainsString('+372 555 0123', $html);
        $this->assertStringContainsString($settings->localized('clinic.address', $locale), $html);
        $url = (new OnlineLeafletUrl($doc))->onlineUrl();
        $this->assertSame(route('online.show', ['locale' => $locale, 'combo' => $procedures->reverse()->pluck('slug')->implode('+')]), $url);
        $this->assertStringNotContainsString('PrivatePatient', $url);
        $this->assertStringNotContainsString('2025', $url);
        $this->assertStringNotContainsString('?', $url);
        $this->assertStringContainsString('src="'.app(QrCodeFactory::class)->dataUri($url).'"', $html);
        $this->assertStringContainsString(e(__('pdf.created', ['date' => $date, 'version' => 'v7'], $locale)), $html);
        $this->assertStringContainsString(e(__('pdf.back_emergency', [], $locale)), strip_tags($html));
        $this->assertStringContainsString('<b>112</b>', $html);
        $this->assertStringContainsString(e(__('pdf.back_contact', [], $locale)), $html);
        $this->assertStringNotContainsString('PrivatePatient', $html);
        $this->assertStringNotContainsString('2025-01-13', $html);
    }

    public function test_back_is_last_page_without_running_footer_and_with_text_inside_safe_zone(): void
    {
        $tool = (new ExecutableFinder)->find('pdftotext');
        if ($tool === null) {
            $this->markTestSkipped('Poppler pdftotext is unavailable.');
        }
        config(['leaflet.draft_watermark' => false]);
        $this->travelTo(now()->setDate(2026, 11, 24));
        $procedure = Procedure::factory()->withContent()->create();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        foreach ([false, true] as $printMode) {
            $pdf = app(PdfRenderer::class)->render($doc, $printMode);
            $process = new Process([$tool, '-layout', '-', '-']);
            $process->setInput($pdf)->mustRun();
            $pages = explode("\f", trim($process->getOutput(), "\f\r\n "));
            $last = end($pages);
            $this->assertStringContainsString('Koostatud 24.11.2026', $last);
            $this->assertStringContainsString('Hädaolukorras helista 112', $last);
            $this->assertDoesNotMatchRegularExpression('/\d+\s*\/\s*'.count($pages).'\b/', $last);
            $bounds = new Process([$tool, '-f', (string) count($pages), '-bbox', '-', '-']);
            $bounds->setInput($pdf)->mustRun();
            preg_match_all('/<word xMin="([\d.]+)" yMin="([\d.]+)" xMax="([\d.]+)" yMax="([\d.]+)"/', $bounds->getOutput(), $words, PREG_SET_ORDER);
            $this->assertNotEmpty($words);
            $offset = $printMode ? 10 : 0;
            foreach ($words as $word) {
                $this->assertGreaterThanOrEqual($offset + 5, (float) $word[1] * 25.4 / 72);
                $this->assertGreaterThanOrEqual($offset + 5, (float) $word[2] * 25.4 / 72);
                $this->assertLessThanOrEqual($offset + 143, (float) $word[3] * 25.4 / 72);
                $this->assertLessThanOrEqual($offset + 205, (float) $word[4] * 25.4 / 72);
            }
        }
    }
}
