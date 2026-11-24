<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Procedure;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WizardTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_landing_has_localized_content_and_clinic_contacts(string $locale): void
    {
        $settings = app(Settings::class);
        $settings->set('clinic.name', ['et' => 'Meie kliinik', 'en' => 'Our clinic']);
        $settings->set('clinic.phone', '+372 555 1234');
        $settings->set('leaflet.disclaimer', ['et' => 'Kliiniku nõuanne.', 'en' => 'Clinic advice.']);

        $this->get('/'.$locale)->assertOk()
            ->assertSee(__('wizard.headline', [], $locale))
            ->assertSee(__('wizard.start', [], $locale))
            ->assertSee('href="'.url('/'.$locale.'/leaflet').'"', false)
            ->assertSee('src="'.asset('brand/logo.svg').'"', false)
            ->assertSee('alt="'.$settings->localized('clinic.name', $locale).'"', false)
            ->assertSee('tel:+3725551234', false)
            ->assertSee('+372 555 1234')
            ->assertSee('tel:112', false)
            ->assertSee($settings->localized('leaflet.disclaimer', $locale))
            ->assertSee('Eesti keeles')->assertSee('In English');
    }

    #[DataProvider('locales')]
    public function test_picker_groups_active_drafts_in_category_and_procedure_order(string $locale): void
    {
        $last = Category::factory()->create(['sort' => 20, 'name' => ['et' => 'Viimane', 'en' => 'Last']]);
        $first = Category::factory()->create(['slug' => 'dental', 'sort' => 1, 'name' => ['et' => 'Esimene', 'en' => 'First']]);
        $empty = Category::factory()->create(['name' => ['et' => 'Tühi', 'en' => 'Empty']]);
        $onlyInactive = Category::factory()->create(['name' => ['et' => 'Peidetud rühm', 'en' => 'Hidden group']]);
        $later = Procedure::factory()->for($first)->draft()->create(['sort' => 2, 'name' => ['et' => 'Teine ravi', 'en' => 'Second treatment']]);
        $earlier = Procedure::factory()->for($first)->draft()->create(['sort' => 1, 'name' => ['et' => 'Õmbluse eemaldamine', 'en' => 'Suture removal'], 'search_keywords' => ['et' => 'õmblus š ž', 'en' => 'stitch']]);
        $lastProcedure = Procedure::factory()->for($last)->create();
        $inactive = Procedure::factory()->for($first)->create(['is_active' => false, 'name' => ['et' => 'Peidetud ravi', 'en' => 'Hidden treatment']]);
        Procedure::factory()->for($onlyInactive)->create(['is_active' => false]);

        $response = $this->get('/'.$locale.'/leaflet')->assertOk()
            ->assertSeeInOrder([$first->getTranslation('name', $locale), $earlier->getTranslation('name', $locale), $later->getTranslation('name', $locale), $last->getTranslation('name', $locale), $lastProcedure->getTranslation('name', $locale)])
            ->assertDontSee($empty->getTranslation('name', $locale))
            ->assertDontSee($onlyInactive->getTranslation('name', $locale))
            ->assertDontSee($inactive->getTranslation('name', $locale))
            ->assertSee('name="procedures[]"', false)
            ->assertSee('action="'.url('/'.$locale.'/leaflet').'"', false)
            ->assertSee('x-cloak x-show="true"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee(file_get_contents(public_path('icons/categories/dental.svg')), false);

        $otherLocale = $locale === 'et' ? 'en' : 'et';
        $response->assertDontSee($earlier->getTranslation('name', $otherLocale));
    }

    #[DataProvider('locales')]
    public function test_empty_selection_returns_a_localized_error(string $locale): void
    {
        $this->from('/'.$locale.'/leaflet')->post('/'.$locale.'/leaflet', [])
            ->assertRedirect('/'.$locale.'/leaflet')
            ->assertSessionHasErrors(['procedures' => __('wizard.validation.required', [], $locale)]);

        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/'.$locale.'/leaflet')->assertSee(__('wizard.validation.required', [], $locale));
    }

    public function test_inactive_unknown_and_duplicate_ids_are_rejected_without_changing_the_selection(): void
    {
        $valid = Procedure::factory()->create();
        $inactive = Procedure::factory()->create(['is_active' => false]);

        foreach ([[$inactive->id], [999999], [$valid->id, $valid->id]] as $ids) {
            $this->withSession(['wizard.procedures' => [$valid->id]])
                ->from('/et/leaflet')->post('/et/leaflet', ['procedures' => $ids])
                ->assertRedirect('/et/leaflet')->assertSessionHasErrors('procedures.0')
                ->assertSessionHas('wizard.procedures', [$valid->id]);
        }
    }

    #[DataProvider('locales')]
    public function test_selection_is_saved_in_submitted_order_and_prechecked_on_return(string $locale): void
    {
        $procedures = Procedure::factory()->count(2)->sequence(
            ['name' => ['et' => 'Esimene valik', 'en' => 'First choice']],
            ['name' => ['et' => 'Teine valik', 'en' => 'Second choice']],
        )->create();
        $ids = [$procedures[1]->id, $procedures[0]->id];

        $this->post('/'.$locale.'/leaflet', ['procedures' => array_map('strval', $ids)])
            ->assertSessionHasNoErrors()->assertSessionHas('wizard.procedures', $ids)
            ->assertRedirect('/'.$locale.'/leaflet/details');

        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/'.$locale.'/leaflet/details')->assertOk()
            ->assertSeeInOrder([$procedures[1]->getTranslation('name', $locale), $procedures[0]->getTranslation('name', $locale)])
            ->assertSee('href="'.url('/'.$locale.'/leaflet').'"', false);

        $html = $this->get('/'.$locale.'/leaflet')->assertOk()->getContent();
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/<input\b[^>]*id="procedure-'.$id.'"[^>]*\bchecked\b/s', $html);
        }
    }

    public function test_more_than_five_procedures_are_rejected(): void
    {
        $ids = Procedure::factory()->count(6)->create()->modelKeys();
        $this->from('/et/leaflet')->post('/et/leaflet', ['procedures' => $ids])
            ->assertRedirect('/et/leaflet')
            ->assertSessionHasErrors(['procedures' => __('wizard.validation.max', [], 'et')])
            ->assertSessionMissing('wizard.procedures');
    }

    public function test_five_procedures_are_accepted_and_empty_catalogue_is_explained(): void
    {
        $this->get('/et/leaflet')->assertOk()->assertSee(__('wizard.empty', [], 'et'));
        $ids = Procedure::factory()->count(5)->create()->modelKeys();
        $this->post('/et/leaflet', ['procedures' => $ids])
            ->assertSessionHasNoErrors()->assertSessionHas('wizard.procedures', $ids);
    }
}
