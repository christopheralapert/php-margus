<?php

namespace Tests\Feature;

use App\Enums\AdviceSection;
use App\Models\AdviceItem;
use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Support\Settings;
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
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertSee('<title>'.e($ordered->map(fn ($procedure) => $procedure->getTranslation('name', $locale))->implode(', ')), false)
            ->assertDontSee('PrivatePatient')->assertDontSee('2025-01-13');
    }

    public function test_unknown_inactive_empty_or_more_than_five_slugs_are_not_found(): void
    {
        $procedure = Procedure::factory()->create();
        $inactive = Procedure::factory()->create(['is_active' => false]);
        foreach (['missing', $procedure->slug.'+missing', $inactive->slug, $procedure->slug.'+', implode('+', array_fill(0, 6, $procedure->slug))] as $combo) {
            $this->get(route('online.show', ['locale' => 'et', 'combo' => $combo]))->assertNotFound();
            $this->get(route('online.personalize', ['locale' => 'et', 'combo' => $combo]))->assertNotFound();
        }
    }

    #[DataProvider('locales')]
    public function test_online_and_preview_render_identical_shared_content_with_merged_advice(string $locale): void
    {
        $procedures = Procedure::factory()->withContent()->count(2)->create();
        foreach ($procedures as $index => $procedure) {
            AdviceItem::factory()->for($procedure)->create([
                'section' => AdviceSection::Dont,
                'merge_key' => 'shared.rest',
                'text' => ['et' => 'Shared rest '.$index, 'en' => 'Shared rest '.$index],
            ]);
        }
        app()->setLocale($locale);
        $doc = app(LeafletBuilder::class)->build($procedures->modelKeys(), $locale, null, null);
        $content = view('leaflet._content', compact('doc'))->render();
        $online = $this->get(route('online.show', ['locale' => $locale, 'combo' => $procedures->pluck('slug')->implode('+')]))
            ->assertOk()->assertSee($content, false)->assertSee('data-general-advice', false)
            ->assertSee('Shared rest 0')->assertDontSee('Shared rest 1');
        $this->assertSame(1, substr_count($online->getContent(), 'Shared rest 0'));
        $this->withSession(['wizard.procedures' => $procedures->modelKeys()])
            ->get('/'.$locale.'/leaflet/preview')->assertOk()->assertSee($content, false);
    }

    #[DataProvider('locales')]
    public function test_chat_kill_switch_and_localized_safety_controls(string $locale): void
    {
        $procedure = Procedure::factory()->create();
        $url = route('online.show', ['locale' => $locale, 'combo' => $procedure->slug]);
        $settings = app(Settings::class);
        $settings->set('clinic.phone', '+372 555 1234');
        config(['chat.enabled' => false]);
        $this->get($url)->assertOk()->assertDontSee('data-chat', false)->assertDontSee('chat-disclaimer', false)
            ->assertDontSee(__('chat.needs_javascript', [], $locale));
        config(['chat.enabled' => true, 'chat.max_chars' => 321]);
        $this->get($url)->assertOk()->assertSee('data-chat', false)
            ->assertSee(__('chat.disclaimer', ['phone' => '+372 555 1234', 'emergency' => $settings->get('clinic.emergency')], $locale))
            ->assertSee('href="tel:+3725551234"', false)->assertSee('href="tel:112"', false)
            ->assertSee('maxlength="321"', false)->assertSee('aria-live="polite"', false)
            ->assertSee(__('chat.needs_javascript', [], $locale));
    }

    #[DataProvider('locales')]
    public function test_make_pdf_validates_and_preselects_procedures_in_the_picker(string $locale): void
    {
        $procedures = Procedure::factory()->count(2)->create();
        $params = ['locale' => $locale, 'combo' => $procedures->pluck('slug')->implode('+')];
        $this->get(route('online.show', $params))->assertOk()->assertSee(route('online.personalize', $params));
        $this->withSession(['wizard.first_name' => 'OldName', 'wizard.date' => '2025-01-13', 'wizard.consent' => true])
            ->get(route('online.personalize', $params))->assertRedirect(route('wizard.procedures', ['locale' => $locale]))
            ->assertSessionHas('wizard.procedures', $procedures->modelKeys())
            ->assertSessionMissing('wizard.first_name')->assertSessionMissing('wizard.date')->assertSessionMissing('wizard.consent');
        $html = $this->get('/'.$locale.'/leaflet')->assertOk()->getContent();
        foreach ($procedures as $procedure) {
            $this->assertMatchesRegularExpression('/id="procedure-'.$procedure->id.'"[^>]*checked/s', $html);
        }
    }
}
