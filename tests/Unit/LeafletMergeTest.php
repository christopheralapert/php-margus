<?php

namespace Tests\Unit;

use App\Enums\AdviceSection;
use App\Models\AdviceItem;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\LeafletDocument;
use App\Services\Leaflet\PdfAdvice;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeafletMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_procedure_keeps_all_advice_even_with_repeated_keys(): void
    {
        $procedure = Procedure::factory()->create();
        $items = AdviceItem::factory()->for($procedure)->count(2)->create([
            'section' => AdviceSection::Dont, 'merge_key' => 'general.no_smoking_48h',
        ]);
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'en', null, null);

        $this->assertSame([], $doc->general());
        $this->assertSame($items->modelKeys(), $doc->advice($doc->sections[0]['procedure'], AdviceSection::Dont)->modelKeys());
    }

    public function test_only_keys_shared_by_distinct_selected_procedures_are_removed(): void
    {
        $procedures = Procedure::factory()->count(3)->create();
        foreach ($procedures as $procedure) {
            $this->item($procedure, AdviceSection::Dont, 'general.no_smoking_48h');
        }
        $unique = $this->item($procedures[0], AdviceSection::Dont, 'selected.only');
        $this->item($procedures[2], AdviceSection::Dont, 'selected.only');
        $repeated = AdviceItem::factory()->for($procedures[0])->count(2)->create([
            'section' => AdviceSection::Do, 'merge_key' => 'same.procedure',
        ]);
        $doc = app(LeafletBuilder::class)->build([$procedures[0]->id, $procedures[1]->id], 'en', null, null);

        $this->assertSame(['dont'], array_keys($doc->general()));
        $this->assertSame(['general.no_smoking_48h'], $doc->general()['dont']->pluck('merge_key')->all());
        $this->assertSame([$unique->id], $doc->advice($doc->sections[0]['procedure'], AdviceSection::Dont)->modelKeys());
        $this->assertTrue($doc->advice($doc->sections[1]['procedure'], AdviceSection::Dont)->isEmpty());
        $this->assertSame($repeated->modelKeys(), $doc->advice($doc->sections[0]['procedure'], AdviceSection::Do)->modelKeys());
    }

    public function test_three_procedures_keep_selection_section_and_first_appearance_order(): void
    {
        $procedures = Procedure::factory()->count(3)->create();
        foreach ($procedures as $index => $procedure) {
            $this->item($procedure, AdviceSection::Pain, 'shared.pain', 'Pain '.$index, 0);
            $this->item($procedure, AdviceSection::Do, 'shared.second', 'Second '.$index, $index === 2 ? 1 : 2);
            $this->item($procedure, AdviceSection::Do, 'shared.first', 'First '.$index, $index === 2 ? 2 : 1);
        }
        $ids = [$procedures[2]->id, $procedures[0]->id, $procedures[1]->id];
        $doc = app(LeafletBuilder::class)->build($ids, 'en', null, null);

        $this->assertSame($ids, array_map(fn ($section) => $section['procedure']->id, $doc->sections));
        $this->assertSame(['do', 'pain'], array_keys($doc->general()));
        $this->assertSame(['shared.second', 'shared.first'], $doc->general()['do']->pluck('merge_key')->all());
        $this->assertSame('Second 2', $doc->general()['do']->first()->getTranslation('text', 'en'));
        $this->assertSame('Pain 2', $doc->general()['pain']->first()->getTranslation('text', 'en'));
    }

    public function test_shared_red_flags_and_general_prefix_come_first_then_specific_groups(): void
    {
        $procedures = Procedure::factory()->count(3)->create();
        foreach ($procedures as $index => $procedure) {
            $this->item($procedure, AdviceSection::RedFlag, null, 'Specific '.$index, 0);
            $this->item($procedure, AdviceSection::RedFlag, 'shared.warning', 'Shared '.$index, 1);
        }
        $this->item($procedures[0], AdviceSection::RedFlag, 'general.unique', 'General', 2);
        $ids = [$procedures[2]->id, $procedures[0]->id, $procedures[1]->id];
        $doc = app(LeafletBuilder::class)->build($ids, 'en', null, null);
        $flags = app(PdfAdvice::class)->redFlags($doc);

        $this->assertSame([], $doc->general());
        $this->assertSame([null, null, ...$ids], array_map(fn ($flag) => $flag['procedure']?->id, $flags));
        $this->assertSame(['Shared 2', 'General', 'Specific 2', 'Specific 0', 'Specific 1'], array_map(fn ($flag) => $flag['item']->getTranslation('text', 'en'), $flags));
    }

    public static function medicationCases(): array
    {
        return [['et', false], ['et', true], ['en', false], ['en', true]];
    }

    #[DataProvider('medicationCases')]
    public function test_general_pain_has_prescription_once_and_specific_pain_does_not_repeat_it(string $locale, bool $existing): void
    {
        $procedures = Procedure::factory()->count(3)->create();
        $fixed = __('pdf.medication_advice', [], $locale);
        foreach ($procedures as $index => $procedure) {
            $this->item($procedure, AdviceSection::Pain, 'shared.pain', $existing ? $fixed : 'Rest.', 0);
            $this->item($procedure, AdviceSection::Pain, null, 'Specific pain '.$index, 1);
        }
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), $locale, null, null);
        $html = $this->pdfHtml($doc);

        $this->assertSame(1, substr_count($html, e($fixed)));
        [$general, $specific] = explode('<pagebreak page-selector="section-0" />', $html);
        $this->assertStringContainsString(e($fixed), $general);
        $this->assertStringNotContainsString(e($fixed), $specific);
        foreach ($procedures as $index => $procedure) {
            $this->assertStringContainsString('Specific pain '.$index, $specific);
        }
    }

    public function test_without_general_pain_each_nonempty_procedure_pain_tile_keeps_the_fixed_line(): void
    {
        $procedures = Procedure::factory()->count(2)->create();
        foreach ($procedures as $procedure) {
            $this->item($procedure, AdviceSection::Do, 'shared.rest');
            $this->item($procedure, AdviceSection::Pain, null, 'Specific pain');
        }
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), 'en', null, null);

        $this->assertSame(2, substr_count($this->pdfHtml($doc), e(__('pdf.medication_advice', [], 'en'))));
    }

    public function test_empty_cards_and_tiles_disappear_from_reduced_procedures(): void
    {
        $procedures = Procedure::factory()->count(2)->create();
        foreach ($procedures as $procedure) {
            $this->item($procedure, AdviceSection::Dont, 'shared.dont');
            $this->item($procedure, AdviceSection::Pain, 'shared.pain');
        }
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), 'en', null, null);
        $html = $this->pdfHtml($doc);
        [$general, $specific] = explode('<pagebreak page-selector="section-0" />', $html);

        $this->assertStringContainsString('advice-card', $general);
        $this->assertStringContainsString('general-topics', $general);
        $this->assertStringNotContainsString('topic-group', $general);
        $this->assertStringNotContainsString('advice-card', $specific);
        $this->assertStringNotContainsString('topic-group', $specific);
    }

    #[DataProvider('locales')]
    public function test_general_topics_use_compact_rows_and_chips_keep_names_together(string $locale): void
    {
        $procedures = Procedure::factory()->count(2)->create([
            'name' => ['et' => 'Hamba eemaldamine', 'en' => 'Tooth extraction'],
        ]);
        foreach ($procedures as $procedure) {
            foreach ([AdviceSection::Pain, AdviceSection::Eating, AdviceSection::Sleep] as $section) {
                $this->item($procedure, $section, 'shared.'.$section->value, 'Shared '.$section->value);
                $this->item($procedure, $section, null, 'Specific '.$section->value);
            }
        }
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), $locale, null, null);
        [, $body] = explode('<pagebreak page-selector="general" />', $this->pdfHtml($doc));
        [$general, $specific] = explode('<pagebreak page-selector="section-0" />', $body);

        $this->assertSame(1, substr_count($general, 'class="general-topics'));
        $this->assertSame(3, substr_count($general, 'class="general-topic-row'));
        $this->assertStringContainsString(__('pdf.everyday_life', [], $locale), $general);
        $this->assertStringContainsString(str_replace(' ', "\u{00A0}", $procedures[0]->getTranslation('name', $locale)), $general);
        $this->assertSame(2, substr_count($general, 'class="strong general-chip"'));
        $this->assertStringNotContainsString('topic-panel', $general);
        $this->assertStringContainsString('topic-panel', $specific);
        $this->assertMatchesRegularExpression('/<div class="keep">\s*<h2[^>]*>.*?<\/h2>.*?Shared pain.*?Shared eating.*?<\/div>.*?Shared sleep/s', $general);
    }

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    public function test_real_seed_advice_merges_once_and_keeps_procedure_specific_content(): void
    {
        $this->seed(DatabaseSeeder::class);
        $ids = collect(['knee-arthroscopy', 'tooth-extraction', 'mole-removal'])
            ->map(fn ($slug) => Procedure::where('slug', $slug)->value('id'))->all();
        $doc = app(LeafletBuilder::class)->build($ids, 'et', null, null);

        $this->assertCount(1, $doc->general()['pain']);
        $this->assertSame('general.pain_as_prescribed', $doc->general()['pain']->first()->merge_key);
        $this->assertSame(1, substr_count($this->pdfHtml($doc), e(__('pdf.medication_prescribed_phrase', [], 'et'))));
        foreach ($doc->sections as ['procedure' => $procedure]) {
            $this->assertTrue($doc->advice($procedure, AdviceSection::Do)->isNotEmpty());
            $this->assertFalse($doc->advice($procedure, AdviceSection::Pain)->contains('merge_key', 'general.pain_as_prescribed'));
        }
    }

    private function item(Procedure $procedure, AdviceSection $section, ?string $key, string $text = 'Advice', int $sort = 0): AdviceItem
    {
        return AdviceItem::factory()->for($procedure)->create([
            'section' => $section, 'merge_key' => $key, 'text' => ['et' => $text, 'en' => $text], 'sort' => $sort,
        ]);
    }

    private function pdfHtml(LeafletDocument $doc): string
    {
        return view('pdf.a5.document', ['doc' => $doc, 'settings' => app(Settings::class), 'printMode' => false])->render();
    }
}
