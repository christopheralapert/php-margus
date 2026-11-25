<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DateFormatTest extends TestCase
{
    use RefreshDatabase;

    public static function validDates(): array
    {
        return [
            ['et', '24.11.2026', '2026-11-24', '24.11.2026'],
            ['et', '1.2.2026', '2026-02-01', '01.02.2026'],
            ['et', '24/11/2026', '2026-11-24', '24.11.2026'],
            ['et', '24-11-2026', '2026-11-24', '24.11.2026'],
            ['en', '24/11/2026', '2026-11-24', '24/11/2026'],
            ['en', '1/2/2026', '2026-02-01', '01/02/2026'],
            ['en', '24.11.2026', '2026-11-24', '24/11/2026'],
        ];
    }

    #[DataProvider('validDates')]
    public function test_locale_input_is_stored_as_iso_and_redisplayed_in_the_locale_format(string $locale, string $input, string $stored, string $display): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $procedure = Procedure::factory()->create();
        $this->withSession(['wizard.procedures' => [$procedure->id]])
            ->post('/'.$locale.'/leaflet/details', ['date' => $input, 'consent' => 1])
            ->assertRedirect('/'.$locale.'/leaflet/preview')->assertSessionHasNoErrors()
            ->assertSessionHas('wizard.date', $stored);

        $this->get('/'.$locale.'/leaflet/details')->assertOk()
            ->assertSee('value="'.$display.'"', false)
            ->assertSee('placeholder="'.__('wizard.date_placeholder', [], $locale).'"', false)
            ->assertSee(__('wizard.date_hint', [], $locale))
            ->assertSee('inputmode="numeric" autocomplete="off" maxlength="10"', false)
            ->assertSee('aria-describedby="date-hint date-error"', false)
            ->assertSee('for="date"', false)
            ->assertDontSee('value="'.$stored.'"', false);
    }

    public static function invalidDates(): array
    {
        $cases = [];
        foreach (['et', 'en'] as $locale) {
            foreach (['31.02.2026', '00.13.2026', '29.02.2026', '24.00.2026', '24.11.0000', 'letters', '2026-11-24', '24.11.26', '24/11.2026', '24.11.2026abc', ['24.11.2026']] as $input) {
                $cases[] = [$locale, $input];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_dates_have_a_localized_error_and_do_not_change_the_stored_date(string $locale, mixed $input): void
    {
        $procedure = Procedure::factory()->create();
        $this->withSession(['wizard.procedures' => [$procedure->id], 'wizard.date' => '2026-11-23'])
            ->from('/'.$locale.'/leaflet/details')
            ->post('/'.$locale.'/leaflet/details', ['date' => $input, 'consent' => 1])
            ->assertRedirect('/'.$locale.'/leaflet/details')
            ->assertSessionHasErrors(['date' => __('wizard.validation.date', [], $locale)])
            ->assertSessionHas('wizard.date', '2026-11-23')
            ->assertSessionHasInput('date', $input);
        $this->assertDatabaseCount('consents', 0);
    }

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_date_range_is_inclusive_in_both_locales(string $locale): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(14, 5));
        $procedure = Procedure::factory()->create();
        $this->withSession(['wizard.procedures' => [$procedure->id]]);
        foreach (['24.11.2025' => '2025-11-24', '25.11.2026' => '2026-11-25'] as $input => $stored) {
            $this->post('/'.$locale.'/leaflet/details', ['date' => $input, 'consent' => 1])
                ->assertSessionHasNoErrors()->assertSessionHas('wizard.date', $stored);
        }
        foreach (['23.11.2025', '26.11.2026'] as $input) {
            $this->post('/'.$locale.'/leaflet/details', ['date' => $input, 'consent' => 1])
                ->assertSessionHasErrors(['date' => __('wizard.validation.date_range', [], $locale)]);
        }
    }

    public function test_invalid_date_is_redisplayed_with_accessible_localized_error(): void
    {
        $procedure = Procedure::factory()->create();
        $message = 'Sisesta kuupäev kujul pp.kk.aaaa, näiteks 24.11.2026.';
        $this->withSession(['wizard.procedures' => [$procedure->id]])
            ->from('/et/leaflet/details')->post('/et/leaflet/details', ['date' => '31.02.2026', 'consent' => 1])
            ->assertSessionHasErrors(['date' => $message]);
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/et/leaflet/details')->assertOk()->assertSee('value="31.02.2026"', false)
            ->assertSee('aria-describedby="date-hint date-error"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee($message);
    }

    #[DataProvider('locales')]
    public function test_account_and_admin_dates_use_the_page_locale(string $locale): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $procedure = Procedure::factory()->create(['updated_at' => '2026-11-24 14:05:00', 'reviewed_at' => '2026-11-23 09:07:00']);
        SavedLeaflet::factory()->for($user)->create([
            'locale' => $locale === 'et' ? 'en' : 'et',
            'procedure_ids' => [$procedure->id],
            'created_at' => '2026-11-24 14:05:00',
            'last_downloaded_at' => '2026-11-23 09:07:00',
        ]);
        $date = $locale === 'et' ? '24.11.2026' : '24 Nov 2026';
        $reviewed = $locale === 'et' ? '23.11.2026 09:07' : '23 Nov 2026 09:07';
        $this->actingAs($user)->get('/'.$locale.'/account/leaflets')->assertOk()
            ->assertSee($date.' 14:05')->assertSee($reviewed);
        $this->get('/'.$locale.'/admin/procedures')->assertOk()->assertSee($date)->assertDontSee('2026-11-24');
        $this->get('/'.$locale.'/admin/procedures/'.$procedure->id.'/edit')->assertOk()
            ->assertSee($reviewed)->assertDontSee('2026-11-23 09:07');
    }

    public function test_views_do_not_use_native_date_inputs(): void
    {
        foreach (File::allFiles(resource_path('views')) as $view) {
            $this->assertDoesNotMatchRegularExpression('/\btype\s*=\s*["\']date["\']/i', $view->getContents(), $view->getRelativePathname());
        }
    }
}
