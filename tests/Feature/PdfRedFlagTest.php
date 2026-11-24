<?php

namespace Tests\Feature;

use App\Enums\AdviceSection;
use App\Models\AdviceItem;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfAdvice;
use App\Services\Leaflet\PdfIcons;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfRedFlagTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_contacts_are_settings_driven_and_warning_is_svg(string $locale): void
    {
        $procedure = Procedure::factory()->create();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, null);
        $html = view('pdf.a5.redflag', compact('doc'))->render();
        $this->assertStringContainsString('112', $html);
        foreach (['clinic.phone' => '+372 555 0100', 'clinic.after_hours_phone' => '+372 555 0101', 'clinic.emergency' => '999'] as $key => $value) {
            app(Settings::class)->set($key, $value);
        }
        $html = view('pdf.a5.redflag', compact('doc'))->render();
        foreach (['+372 555 0100', '+372 555 0101', '999'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $this->assertStringContainsString(e(mb_strtoupper(__('pdf.red_flags', [], $locale), 'UTF-8')), $html);
        $this->assertStringContainsString(e(__('pdf.after_hours', ['phone' => '+372 555 0101'], $locale)), $html);
        $this->assertStringNotContainsString('⚠', $html);
        $this->assertStringContainsString(app(PdfIcons::class)->image('warning-triangle', '#FFFFFF', 7), $html);
        preg_match_all('/data:image\/svg\+xml;base64,([^"]+)/', $html, $images);
        foreach ($images[1] as $image) {
            $svg = base64_decode($image);
            $this->assertStringContainsString('<svg', $svg);
            $this->assertStringNotContainsString('<text', $svg);
        }
    }

    public function test_general_flags_are_first_and_all_nonempty_merge_keys_are_deduplicated(): void
    {
        $procedures = Procedure::factory()->count(2)->sequence(
            ['name' => ['et' => 'Esimene protseduur', 'en' => 'First procedure']],
            ['name' => ['et' => 'Teine protseduur', 'en' => 'Second procedure']],
        )->create();
        foreach ($procedures as $index => $procedure) {
            foreach ([null, 'shared.specific', 'general.fever', 'general.emergency', ''] as $sort => $key) {
                AdviceItem::factory()->for($procedure)->create([
                    'section' => AdviceSection::RedFlag, 'merge_key' => $key, 'sort' => $sort,
                    'text' => ['et' => 'Flag '.$index.'-'.$sort],
                ]);
            }
            AdviceItem::factory()->for($procedure)->create([
                'section' => AdviceSection::RedFlag, 'merge_key' => 'general.blank', 'text' => ['et' => '  '],
            ]);
        }
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), 'et', null, null);
        $flags = app(PdfAdvice::class)->redFlags($doc);
        $this->assertSame(['shared.specific', 'general.fever', 'general.emergency', null, '', null, ''], array_map(fn ($flag) => $flag['item']->merge_key, $flags));
        $this->assertNull($flags[0]['procedure']);
        $html = view('pdf.a5.redflag', compact('doc'))->render();
        $this->assertSame(1, substr_count($html, 'Flag 0-2'));
        $this->assertStringNotContainsString('Flag 1-2', $html);
        $this->assertSame(2, substr_count($html, 'class="redflag-group"'));
        foreach ($procedures as $procedure) {
            $this->assertSame(1, substr_count($html, e($procedure->getTranslation('name', 'et'))));
        }
        $this->assertLessThan(strpos($html, 'class="redflag-group"'), strpos($html, 'Flag 0-3'));
        foreach (['Flag 0-0', 'Flag 0-1', 'Flag 0-4', 'Flag 1-0', 'Flag 1-4'] as $text) {
            $this->assertSame(1, substr_count($html, $text));
        }
        $single = app(LeafletBuilder::class)->build([$procedures[0]->id], 'et', null, null);
        $this->assertStringNotContainsString('class="redflag-group"', view('pdf.a5.redflag', ['doc' => $single])->render());
    }

    public function test_followups_only_render_after_the_redflag_page_break_and_skip_empty_followup_even_with_a_date(): void
    {
        $procedures = Procedure::factory()->count(2)->create();
        $empty = Procedure::factory()->create(['follow_up' => ['et' => '  ', 'en' => '']]);
        $doc = app(LeafletBuilder::class)->build([...$procedures->modelKeys(), $empty->id], 'et', null, '2026-11-24');
        $settings = app(Settings::class);
        $html = view('pdf.a5.document', ['doc' => $doc, 'settings' => $settings, 'printMode' => false])->render();
        [$before, $after] = explode('<pagebreak page-selector="redflags" />', $html);
        $this->assertStringNotContainsString('followup-box', $before);
        $this->assertSame(2, substr_count($after, 'class="followup-box'));
        $this->assertSame(2, substr_count($after, 'Protseduur tehti: 24.11.2026'));
        $this->assertLessThan(strpos($after, 'followup-box'), strpos($after, 'redflag-box'));
        foreach ($procedures as $procedure) {
            $this->assertStringContainsString(e($procedure->getTranslation('name', 'et')), $after);
        }
        $undated = app(LeafletBuilder::class)->build($procedures->modelKeys(), 'et', null, null);
        $this->assertStringNotContainsString('Protseduur tehti:', view('pdf.a5.redflag-page', ['doc' => $undated])->render());
    }

    #[DataProvider('locales')]
    public function test_multi_procedure_redflag_box_stays_whole_and_followups_continue_after_it(string $locale): void
    {
        $tool = (new ExecutableFinder)->find('pdftotext');
        if (! $tool) {
            $this->markTestSkipped('pdftotext is unavailable.');
        }
        config(['leaflet.draft_watermark' => false]);
        $this->seed(DatabaseSeeder::class);
        $ids = Procedure::whereIn('slug', ['tooth-extraction', 'mole-removal', 'knee-arthroscopy'])->pluck('id')->all();
        $doc = app(LeafletBuilder::class)->build($ids, $locale, null, null);
        $process = new Process([$tool, '-raw', '-', '-']);
        $process->setInput(app(PdfRenderer::class)->render($doc));
        $process->mustRun();
        $normalize = fn (string $value): string => preg_replace('/\s+/u', '', $value);
        $pages = array_map($normalize, explode("\f", trim($process->getOutput(), "\f\r\n ")));
        $title = $normalize(mb_strtoupper(__('pdf.red_flags', [], $locale), 'UTF-8'));
        $index = array_key_first(array_filter($pages, fn ($page) => str_contains($page, $title)));
        $this->assertNotNull($index);
        foreach (app(PdfAdvice::class)->redFlags($doc) as $flag) {
            $text = $normalize($flag['item']->getTranslation('text', $locale));
            $this->assertStringContainsString($text, $pages[$index]);
            $this->assertSame(1, substr_count(implode('', $pages), $text));
        }
        $this->assertStringContainsString($normalize(app(Settings::class)->get('clinic.phone')), $pages[$index]);
        $this->assertStringContainsString('112', $pages[$index]);
        foreach ($doc->sections as $section) {
            $followup = $normalize($section['procedure']->getTranslation('follow_up', $locale));
            $this->assertStringNotContainsString($followup, implode('', array_slice($pages, 0, $index)));
            $this->assertStringContainsString($followup, implode('', array_slice($pages, $index)));
        }
        $css = file_get_contents(resource_path('pdf/css/a5.css'));
        $this->assertMatchesRegularExpression('/\.redflag-box\s*\{[^}]*page-break-inside: avoid/', $css);
    }
}
