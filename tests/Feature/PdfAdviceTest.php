<?php

namespace Tests\Feature;

use App\Enums\AdviceSection;
use App\Models\AdviceItem;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfAdvice;
use App\Services\Leaflet\PdfRenderer;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PdfAdviceTest extends TestCase
{
    use RefreshDatabase;

    public static function cardCounts(): array
    {
        return [[5, 1], [6, 2], [7, 2]];
    }

    #[DataProvider('cardCounts')]
    public function test_cards_use_svg_badges_and_split_evenly_at_six_items(int $count, int $columns): void
    {
        $procedure = Procedure::factory()->create();
        foreach ([AdviceSection::Do, AdviceSection::Dont] as $section) {
            for ($i = 1; $i <= $count; $i++) {
                AdviceItem::factory()->for($procedure)->create([
                    'section' => $section, 'sort' => $i,
                    'text' => ['et' => $section->value.' advice '.$i, 'en' => $section->value.' advice '.$i],
                ]);
            }
        }
        $html = $this->partial('cards', $procedure);
        $xpath = $this->xpath($html);
        $cards = $xpath->query('//div[contains(@class,"advice-card")]');
        $this->assertCount(2, $cards);
        $this->assertStringContainsString('Tee nii', $cards->item(0)->textContent);
        $this->assertStringContainsString('Väldi', $cards->item(1)->textContent);
        $this->assertStringNotContainsString('✓', $html);
        $this->assertStringNotContainsString('✕', $html);
        foreach ($cards as $card) {
            $rows = $xpath->query('.//table[contains(@class,"card-row")]', $card);
            $this->assertCount((int) ceil($count / $columns), $rows);
            $this->assertCount(2 * $columns, $xpath->query('.//div[@class="keep"]//td[@class="item-copy"]', $card));
            $this->assertStringContainsString('card-columns-'.$columns, $rows->item(0)->getAttribute('class'));
            if ($columns === 2) {
                $this->assertStringContainsString('advice '.(1 + (int) ceil($count / 2)), $rows->item(0)->textContent);
            }
        }
        $images = $xpath->query('//img');
        $this->assertCount(2 * ($count + 1), $images);
        foreach ($images as $index => $image) {
            $svg = base64_decode(explode(',', $image->getAttribute('src'), 2)[1]);
            $this->assertStringContainsString('<path', $svg);
            $this->assertStringNotContainsString('<text', $svg);
            if ($index === 0 || $index === $count + 1) {
                $this->assertStringContainsString('<circle', $svg);
                $this->assertStringContainsString('#FFFFFF', $svg);
                $this->assertStringContainsString($index === 0 ? '#2F9E5B' : '#D93B3B', $svg);
            }
        }
    }

    public function test_topics_are_ordered_skip_empty_text_and_span_an_odd_last_tile(): void
    {
        $procedure = Procedure::factory()->create();
        foreach ([AdviceSection::Sleep, AdviceSection::Hygiene, AdviceSection::Pain, AdviceSection::Eating] as $section) {
            AdviceItem::factory()->for($procedure)->create([
                'section' => $section, 'text' => ['et' => $section === AdviceSection::Eating ? '  ' : $section->value],
            ]);
        }
        $html = $this->partial('topics', $procedure);
        $xpath = $this->xpath($html);
        $titles = $xpath->query('//table[@class="box-heading mb-s3"]//h2');
        $this->assertSame(['Valu ja ravimid', 'Hügieen', 'Uni'], array_map(fn ($node) => trim($node->textContent), iterator_to_array($titles)));
        $this->assertCount(1, $xpath->query('//td[@colspan="2"]'));
        $this->assertStringContainsString('Uni', $xpath->query('//td[@colspan="2"]')->item(0)->textContent);
        $this->assertCount(1, $xpath->query('//div[contains(@class,"topic-group")][h2 and table[contains(@class,"topic-row")]]'));
        $this->assertCount(2, $xpath->query('//div[contains(@class,"topic-group") and contains(@class,"keep")][table[contains(@class,"topic-row")]]'));
        AdviceItem::factory()->for($procedure)->create(['section' => AdviceSection::Activity, 'text' => ['et' => 'activity']]);
        $this->assertCount(0, $this->xpath($this->partial('topics', $procedure))->query('//td[@colspan="2"]'));
    }

    public static function medicationCases(): array
    {
        return [['et', false], ['et', true], ['en', false], ['en', true]];
    }

    #[DataProvider('medicationCases')]
    public function test_medication_advice_occurs_once_with_or_without_existing_prescription_text(string $locale, bool $existing): void
    {
        $procedure = Procedure::factory()->create();
        $fixed = __('pdf.medication_advice', [], $locale);
        AdviceItem::factory()->for($procedure)->create([
            'section' => AdviceSection::Pain,
            'text' => [$locale => $existing ? $fixed : 'Rest.'],
        ]);
        $html = $this->partial('topics', $procedure, $locale);
        $this->assertSame(1, substr_count($html, e($fixed)));
        // Case folding and non-breaking/multiple whitespace must also prevent duplication.
        $procedure->adviceItems()->first()->update(['text' => [$locale => mb_strtoupper(str_replace(' ', "\u{00A0}  ", $fixed), 'UTF-8')]]);
        $html = $this->partial('topics', $procedure, $locale);
        $this->assertStringNotContainsString(e($fixed), $html);
        $this->assertCount(1, $this->xpath($html)->query('//p[@class="topic-copy"]'));
    }

    public function test_empty_topics_and_cards_are_omitted(): void
    {
        $procedure = Procedure::factory()->create();
        $this->assertSame('', trim($this->partial('topics', $procedure)));
        $this->assertSame('', trim($this->partial('cards', $procedure)));
    }

    public static function dates(): array
    {
        return [['et', '2026-11-24', 'Protseduur tehti: 24.11.2026'], ['en', '2026-11-24', 'Procedure date: 24 Nov 2026'], ['et', null, null], ['en', null, null]];
    }

    #[DataProvider('dates')]
    public function test_followup_uses_the_cover_date_format_only_when_given(string $locale, ?string $date, ?string $expected): void
    {
        $procedure = Procedure::factory()->create();
        $html = $this->partial('followup', $procedure, $locale, $date);
        $this->assertStringContainsString(e($procedure->getTranslation('follow_up', $locale)), $html);
        $this->assertStringContainsString(e(__('pdf.follow_up', [], $locale)), $html);
        if ($expected) {
            $this->assertStringContainsString($expected, $html);
        } else {
            $this->assertStringNotContainsString('Protseduur tehti:', $html);
            $this->assertStringNotContainsString('Procedure date:', $html);
        }
    }

    public function test_followup_accepts_multiple_procedures(): void
    {
        $procedures = Procedure::factory()->count(2)->create();
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), 'en', null, null);
        $html = view('pdf.a5.followup', ['doc' => $doc, 'procedures' => $procedures])->render();
        $this->assertCount(2, $this->xpath($html)->query('//div[contains(@class,"followup-box")]'));
        foreach ($procedures as $procedure) {
            $this->assertStringContainsString(e($procedure->getTranslation('name', 'en')), $html);
        }
    }

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_cards_topics_and_redflags_flow_before_the_back_page_with_extractable_text(string $locale): void
    {
        config(['leaflet.draft_watermark' => false]);
        $textTool = (new ExecutableFinder)->find('pdftotext');
        if (! $textTool) {
            $this->markTestSkipped('pdftotext is unavailable.');
        }
        $this->seed(DatabaseSeeder::class);
        $procedure = Procedure::where('slug', 'tooth-extraction')->firstOrFail();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, '2026-11-24');
        $text = new Process([$textTool, '-raw', '-', '-']);
        $text->setInput(app(PdfRenderer::class)->render($doc));
        $text->mustRun();
        // Raw extraction preserves column order; Poppler may omit word spaces
        // between separately positioned text runs, so compare visible letters.
        $normalize = fn (string $value): string => preg_replace('/\s+/u', '', $value);
        $output = $normalize($text->getOutput());
        $pages = array_map($normalize, explode("\f", rtrim($text->getOutput(), "\f\r\n")));
        $this->assertCount(6, $pages);
        $this->assertStringContainsString($normalize(__('pdf.cards.do', [], $locale)), $pages[2]);
        $this->assertStringContainsString($normalize(__('pdf.cards.dont', [], $locale)), $pages[2]);
        $this->assertStringContainsString($normalize(__('pdf.everyday_life', [], $locale)), $pages[2]);
        // Both tiles in every row, including the spanning last tile, stay on
        // one page. Follow-up moves to the dedicated red-flag page five.
        foreach (array_chunk(app(PdfAdvice::class)->topics($procedure, $locale), 2) as $index => $tiles) {
            foreach ($tiles as $tile) {
                $page = $pages[$index === 0 ? 2 : 3];
                $this->assertStringContainsString($normalize(__('enums.advice_section.'.$tile['section'], [], $locale)), $page);
                foreach ($tile['paragraphs'] as $paragraph) {
                    $this->assertStringContainsString($normalize($paragraph), $page);
                }
            }
        }
        $this->assertStringNotContainsString($normalize(__('pdf.follow_up', [], $locale)), $pages[3]);
        $this->assertStringContainsString($normalize(__('pdf.follow_up', [], $locale)), $pages[4]);
        $this->assertStringContainsString($normalize($procedure->getTranslation('follow_up', $locale)), $pages[4]);
        $this->assertStringContainsString($normalize(__('pdf.procedure_date', ['date' => $doc->formattedDate()], $locale)), $pages[4]);
        foreach ($procedure->adviceItems->where('section', '!=', AdviceSection::RedFlag) as $item) {
            $this->assertStringContainsString($normalize($item->getTranslation('text', $locale)), $output);
        }
        $this->assertStringContainsString($normalize($procedure->getTranslation('follow_up', $locale)), $output);
        $this->assertStringContainsString($normalize(__('pdf.procedure_date', ['date' => $doc->formattedDate()], $locale)), $output);
    }

    public function test_oversized_card_splits_between_rows_and_preserves_extractable_text(): void
    {
        $textTool = (new ExecutableFinder)->find('pdftotext');
        if (! $textTool) {
            $this->markTestSkipped('pdftotext is unavailable.');
        }
        $procedure = Procedure::factory()->create();
        for ($i = 1; $i <= 32; $i++) {
            AdviceItem::factory()->for($procedure)->create([
                'section' => AdviceSection::Do, 'sort' => $i,
                'text' => ['en' => 'START'.$i.' '.str_repeat('Follow the care instructions. ', 5).' END'.$i],
            ]);
        }
        // Oversized panels flow from the preceding content and keep only their
        // header and first two rows, rather than forcing a new page.
        AdviceItem::factory()->for($procedure)->count(32)->create([
            'section' => AdviceSection::Dont,
            'text' => ['en' => str_repeat('Follow the care instructions. ', 5)],
        ]);
        $html = $this->partial('cards', $procedure, 'en');
        $this->assertStringNotContainsString('<pagebreak', $html);
        $this->assertCount(0, $this->xpath($html)->query('//div[contains(@class,"advice-card") and contains(@class,"keep")]'));
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'en', null, null);
        $text = new Process([$textTool, '-layout', '-', '-']);
        $text->setInput(app(PdfRenderer::class)->render($doc));
        $text->mustRun();
        $pages = explode("\f", $text->getOutput());
        $cardPages = array_filter($pages, fn (string $page): bool => str_contains($page, 'START'));
        $this->assertGreaterThan(1, count($cardPages));
        $first = reset($cardPages);
        $this->assertStringContainsString('Do', $first);
        $this->assertStringContainsString('START1 ', $first);
        $this->assertStringContainsString('START2 ', $first);
        $this->assertSame(1, array_key_first($cardPages), 'The long card should begin after the procedure band on page two.');
        foreach (range(1, 32) as $i) {
            $matches = array_filter($pages, fn (string $page): bool => str_contains($page, 'START'.$i.' '));
            $this->assertCount(1, $matches);
            $this->assertMatchesRegularExpression('/END'.$i.'\b/', reset($matches));
        }
    }

    private function partial(string $name, Procedure $procedure, string $locale = 'et', ?string $date = null): string
    {
        $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, $date);

        return view('pdf.a5.'.$name, ['doc' => $doc, 'procedures' => [$procedure], ...$doc->sections[0]])->render();
    }

    private function xpath(string $html): DOMXPath
    {
        $xml = new DOMDocument;
        @$xml->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($xml);
    }
}
