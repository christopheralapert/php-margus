<?php

namespace Tests\Feature;

use App\Enums\AdviceSection;
use App\Models\AdviceItem;
use App\Models\Procedure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreviewMergeTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_preview_uses_general_advice_only_for_multiple_selected_procedures(string $locale): void
    {
        $procedures = Procedure::factory()->count(2)->create();
        foreach ($procedures as $index => $procedure) {
            AdviceItem::factory()->for($procedure)->create([
                'section' => AdviceSection::Dont, 'merge_key' => 'general.no_smoking_48h',
                'text' => ['et' => 'Shared '.$index, 'en' => 'Shared '.$index],
            ]);
            AdviceItem::factory()->for($procedure)->create([
                'section' => AdviceSection::Do, 'text' => ['et' => 'Specific '.$index, 'en' => 'Specific '.$index],
            ]);
        }
        $response = $this->withSession(['wizard.procedures' => $procedures->modelKeys()])
            ->get('/'.$locale.'/leaflet/preview')->assertOk()
            ->assertSee('data-general-advice', false)
            ->assertSee(__('pdf.general_advice', [], $locale))
            ->assertSeeInOrder(['data-general-advice', 'Shared 0', 'Specific 0', 'Specific 1'])
            ->assertDontSee('Shared 1');
        $this->assertSame(1, substr_count($response->getContent(), 'Shared 0'));
        $this->withSession(['wizard.procedures' => [$procedures[0]->id]])
            ->get('/'.$locale.'/leaflet/preview')->assertOk()
            ->assertDontSee('data-general-advice', false)->assertSee('Shared 0')->assertSee('Specific 0');
    }

    #[DataProvider('locales')]
    public function test_preview_uses_the_same_medication_rule_as_the_pdf(string $locale): void
    {
        $procedures = Procedure::factory()->count(2)->create();
        foreach ($procedures as $index => $procedure) {
            AdviceItem::factory()->for($procedure)->create([
                'section' => AdviceSection::Pain, 'merge_key' => 'shared.pain',
                'text' => ['et' => 'Rest.', 'en' => 'Rest.'],
            ]);
            AdviceItem::factory()->for($procedure)->create([
                'section' => AdviceSection::Pain,
                'text' => ['et' => 'Specific '.$index, 'en' => 'Specific '.$index],
            ]);
        }
        $response = $this->withSession(['wizard.procedures' => $procedures->modelKeys()])
            ->get('/'.$locale.'/leaflet/preview')->assertOk()->assertSee('Specific 0')->assertSee('Specific 1');

        $this->assertSame(1, substr_count($response->getContent(), e(__('pdf.medication_advice', [], $locale))));
    }
}
