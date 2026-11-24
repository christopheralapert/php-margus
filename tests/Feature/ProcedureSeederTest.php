<?php

namespace Tests\Feature;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use App\Models\AdviceItem;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\TimelineStep;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProcedureSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProcedureSeederTest extends TestCase
{
    use RefreshDatabase;

    private const CATALOGUE = [
        'dental' => ['tooth-extraction', 'dental-implant'],
        'dermatology' => ['mole-removal', 'cryotherapy'],
        'aesthetic' => ['botulinum-injection', 'dermal-filler'],
        'eye' => ['cataract-surgery'],
        'ent' => ['tonsillectomy', 'nosebleed-cauterisation'],
        'surgery' => ['wound-suturing', 'minor-burn-dressing'],
        'orthopaedics' => ['knee-arthroscopy'],
        'gynaecology' => ['iud-insertion'],
        'vaccination' => ['vaccination', 'allergy-injection'],
    ];

    public function test_catalogue_has_complete_bilingual_draft_content(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('procedures', 15);
        $this->assertDatabaseCount('timeline_steps', 60);
        $this->assertDatabaseCount('categories', count(self::CATALOGUE));

        foreach (self::CATALOGUE as $category => $slugs) {
            $this->assertSame($slugs, Category::where('slug', $category)->firstOrFail()->procedures->pluck('slug')->all());
        }

        foreach (Procedure::with(['timelineSteps', 'adviceItems', 'category'])->get() as $procedure) {
            $this->assertSame(ProcedureStatus::Draft, $procedure->status);
            $this->assertTrue($procedure->is_active);
            $this->assertNull($procedure->reviewed_at);
            $this->assertNull($procedure->reviewed_by);
            $this->assertTranslations($procedure);
            $this->assertSame((new Procedure)->getTranslations('review_note'), $procedure->getTranslations('review_note'));
            $this->assertSame(TimelinePhase::cases(), $procedure->timelineSteps->pluck('phase')->all());
            $this->assertSame([0, 1, 2, 3], $procedure->timelineSteps->pluck('sort')->all());

            foreach ($procedure->timelineSteps as $step) {
                $this->assertTranslations($step);
            }

            foreach (AdviceSection::cases() as $section) {
                $minimum = match ($section) {
                    AdviceSection::Do, AdviceSection::Dont => 4,
                    AdviceSection::RedFlag => 3,
                    default => 1,
                };
                $items = $procedure->adviceItems->where('section', $section);
                $this->assertGreaterThanOrEqual($minimum, $items->count(), $procedure->slug.': '.$section->value);
            }

            foreach ($procedure->adviceItems as $item) {
                $this->assertTranslations($item);
                $this->assertTrue($item->merge_key === null || str_starts_with($item->merge_key, 'general.'));
            }

            foreach (['et', 'en'] as $locale) {
                $redFlags = $procedure->adviceItems->where('section', AdviceSection::RedFlag);
                $this->assertTrue($redFlags->contains(fn ($item) => str_contains($item->getTranslation('text', $locale, false), '112')));
                $this->assertTrue($redFlags->contains(fn ($item) => preg_match('/kliinik|clinic/u', $item->getTranslation('text', $locale, false)) === 1));
            }
        }
    }

    public function test_shared_advice_has_identical_translations_and_sections_across_procedures(): void
    {
        $this->seed(DatabaseSeeder::class);
        $expected = [
            'general.no_smoking_48h' => AdviceSection::Dont,
            'general.no_alcohol_24h' => AdviceSection::Dont,
            'general.no_sauna_week1' => AdviceSection::Dont,
            'general.keep_wound_clean' => AdviceSection::Do,
            'general.rest_first_day' => AdviceSection::Do,
            'general.hydrate' => AdviceSection::Eating,
            'general.pain_as_prescribed' => AdviceSection::Pain,
            'general.red_flag_fever' => AdviceSection::RedFlag,
            'general.red_flag_112' => AdviceSection::RedFlag,
        ];
        $groups = AdviceItem::whereNotNull('merge_key')->get()->groupBy('merge_key');
        $this->assertEqualsCanonicalizing(array_keys($expected), $groups->keys()->all());

        foreach ($groups as $key => $items) {
            $this->assertGreaterThanOrEqual(2, $items->pluck('procedure_id')->unique()->count(), $key);
            $this->assertSame($items->count(), $items->pluck('procedure_id')->unique()->count(), $key);
            foreach ($items as $item) {
                $this->assertSame($expected[$key], $item->section);
                $this->assertSame($items->first()->getTranslations('text'), $item->getTranslations('text'), $key);
            }
        }

        $this->assertStringContainsString('38 °C', $groups['general.red_flag_fever']->first()->getTranslation('text', 'et'));
        $this->assertStringContainsString('helista 112', $groups['general.red_flag_112']->first()->getTranslation('text', 'et'));
        $this->assertStringContainsString('as prescribed by your doctor', $groups['general.pain_as_prescribed']->first()->getTranslation('text', 'en'));
        foreach (Procedure::whereIn('slug', ['vaccination', 'allergy-injection'])->get() as $procedure) {
            $this->assertFalse($procedure->adviceItems()->whereIn('merge_key', ['general.keep_wound_clean', 'general.no_sauna_week1'])->exists());
        }
    }

    public function test_illustrations_are_safe_original_flat_svg_in_the_category_palette(): void
    {
        $this->seed(DatabaseSeeder::class);
        $drawings = [];

        foreach (Procedure::with('category')->get() as $procedure) {
            $this->assertSame('illustrations/'.$procedure->category->slug.'/'.$procedure->slug.'.svg', $procedure->illustration_path);
            $path = public_path($procedure->illustration_path);
            $this->assertFileExists($path);
            $document = new DOMDocument;
            $this->assertTrue($document->load($path, LIBXML_NONET));
            $this->assertNull($document->doctype);
            $this->assertSame('svg', $document->documentElement->localName);
            $this->assertSame('0 0 160 120', $document->documentElement->getAttribute('viewBox'));
            $xpath = new DOMXPath($document);
            $allowed = ['svg', 'path', 'circle', 'rect', 'ellipse', 'polygon', 'line', 'polyline', 'g'];
            $colours = [];

            foreach ($xpath->query('//*') as $element) {
                $this->assertContains($element->localName, $allowed);
                foreach ($element->attributes as $attribute) {
                    $this->assertDoesNotMatchRegularExpression('/^on|^(style|class|href)$/i', $attribute->localName);
                    if ($attribute->nodeName !== 'xmlns') {
                        $this->assertDoesNotMatchRegularExpression('/https?:|url\s*\(/i', $attribute->value);
                    }
                    if (in_array($attribute->localName, ['fill', 'stroke']) && $attribute->value !== 'none') {
                        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $attribute->value);
                        $colours[] = strtoupper($attribute->value);
                    }
                }
            }

            $themeColours = array_values(array_diff(array_unique($colours), ['#1F2937', '#FFFFFF']));
            $this->assertLessThanOrEqual(3, count($themeColours));
            $this->assertContains($procedure->category->color, $themeColours);
            $base = sscanf($procedure->category->color, '#%02x%02x%02x');
            foreach ($themeColours as $colour) {
                $channels = sscanf($colour, '#%02x%02x%02x');
                $ratios = [];
                foreach ($channels as $index => $channel) {
                    $this->assertGreaterThanOrEqual($base[$index], $channel);
                    $ratios[] = (255 - $channel) / (255 - $base[$index]);
                }
                $this->assertLessThan(0.02, max($ratios) - min($ratios));
            }
            $drawings[] = hash_file('sha256', $path);
        }

        $this->assertCount(15, array_unique($drawings));
    }

    public function test_reseeding_replaces_children_and_keeps_counts_and_procedure_ids(): void
    {
        $this->seed(DatabaseSeeder::class);
        $ids = Procedure::orderBy('slug')->pluck('id', 'slug')->all();
        $steps = TimelineStep::count();
        $advice = AdviceItem::count();
        $content = AdviceItem::orderBy('procedure_id')->orderBy('sort')->get()->map(fn ($item) => $item->only(['procedure_id', 'section', 'text', 'merge_key', 'sort']))->all();
        $procedure = Procedure::firstOrFail();
        $procedure->update([
            'name' => ['et' => 'Muudetud', 'en' => 'Changed'],
            'review_note' => ['et' => 'Muudetud', 'en' => 'Changed'],
            'status' => ProcedureStatus::Reviewed,
            'reviewed_by' => 'Reviewer',
            'reviewed_at' => now(),
        ]);
        $procedure->timelineSteps->first()->update(['body' => ['et' => 'Muudetud', 'en' => 'Changed']]);
        AdviceItem::factory()->for($procedure)->create();
        TimelineStep::factory()->for($procedure)->create();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('procedures', 15);
        $this->assertDatabaseCount('timeline_steps', $steps);
        $this->assertDatabaseCount('advice_items', $advice);
        $this->assertSame($ids, Procedure::orderBy('slug')->pluck('id', 'slug')->all());
        $this->assertNotSame('Muudetud', $procedure->fresh()->getTranslation('name', 'et'));
        $this->assertSame((new Procedure)->getTranslations('review_note'), $procedure->fresh()->getTranslations('review_note'));
        $this->assertSame(ProcedureStatus::Draft, $procedure->fresh()->status);
        $this->assertNull($procedure->fresh()->reviewed_by);
        $this->assertNull($procedure->fresh()->reviewed_at);
        $this->assertFalse(TimelineStep::all()->contains(fn ($step) => $step->getTranslation('body', 'et') === 'Muudetud'));
        $this->assertEquals($content, AdviceItem::orderBy('procedure_id')->orderBy('sort')->get()->map(fn ($item) => $item->only(['procedure_id', 'section', 'text', 'merge_key', 'sort']))->all());
    }

    public function test_missing_category_fails_clearly_and_rolls_back_partial_seeding(): void
    {
        $this->seed(CategorySeeder::class);
        Category::where('slug', 'vaccination')->delete();

        try {
            $this->seed(ProcedureSeeder::class);
            $this->fail('Expected missing-category exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Missing category [vaccination]. Run CategorySeeder before ProcedureSeeder.', $exception->getMessage());
        }

        $this->assertDatabaseCount('procedures', 0);
        $this->assertDatabaseCount('timeline_steps', 0);
        $this->assertDatabaseCount('advice_items', 0);
    }

    private function assertTranslations(Model $model): void
    {
        foreach ($model->translatable as $field) {
            $translations = $model->getTranslations($field);
            $this->assertEqualsCanonicalizing(['et', 'en'], array_keys($translations));
            foreach ($translations as $text) {
                $this->assertNotSame('', trim($text), $model->getTable().'.'.$field);
                $this->assertDoesNotMatchRegularExpression('/\b\d+\s?(mg|ml|g|tabl|tablett|tablet|tablets|µg|mcg)\b/i', $text);
                $this->assertDoesNotMatchRegularExpression('/(\d+\s?(x|korda|times)\s?(päevas|a day|daily))/i', $text);
            }
        }
    }
}
