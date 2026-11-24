<?php

namespace Tests\Feature;

use App\Models\Procedure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnlineLeafletTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_online_page_uses_ordered_localized_content_without_session_personalization(string $locale): void
    {
        $procedures = Procedure::factory()->count(5)->create();
        $ordered = $procedures->reverse()->values();
        $this->withSession(['wizard.first_name' => 'PrivatePatient', 'wizard.date' => '2025-01-13'])
            ->get(route('online.show', ['locale' => $locale, 'combo' => $ordered->pluck('slug')->implode('+')]))
            ->assertOk()
            ->assertSeeInOrder($ordered->map(fn (Procedure $procedure): string => $procedure->getTranslation('name', $locale))->all())
            ->assertSee($ordered->first()->getTranslation('summary', $locale))
            ->assertSee(__('pdf.online_soon', [], $locale))
            ->assertDontSee('PrivatePatient')->assertDontSee('2025-01-13');
    }

    public function test_unknown_inactive_empty_or_more_than_five_slugs_are_not_found(): void
    {
        $procedure = Procedure::factory()->create();
        $inactive = Procedure::factory()->create(['is_active' => false]);
        foreach (['missing', $procedure->slug.'+missing', $inactive->slug, $procedure->slug.'+', implode('+', array_fill(0, 6, $procedure->slug))] as $combo) {
            $this->get(route('online.show', ['locale' => 'et', 'combo' => $combo]))->assertNotFound();
        }
    }
}
