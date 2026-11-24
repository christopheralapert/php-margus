<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Support\Settings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_notice_uses_localised_settings_and_is_linked_from_public_pages(): void
    {
        $settings = app(Settings::class);
        $settings->set('clinic.name', ['et' => 'Testkliinik', 'en' => 'Test Clinic']);
        $settings->set('clinic.address', ['et' => 'Testi 7', 'en' => 'Test Street 7']);
        $settings->set('clinic.email', 'privacy@example.test');
        $settings->set('retention.saved_leaflet_months', 7);
        $settings->set('retention.unverified_account_days', 19);
        foreach (['et', 'en'] as $locale) {
            $this->get('/'.$locale.'/privacy')->assertOk()
                ->assertSee($settings->localized('clinic.name', $locale))
                ->assertSee($settings->localized('clinic.address', $locale))
                ->assertSee('privacy@example.test')
                ->assertSee(__('privacy.draft', [], $locale))
                ->assertSee(__('privacy.retention', ['months' => 7, 'days' => 19], $locale));
            $this->get('/'.$locale)->assertOk()->assertSee(route('privacy', ['locale' => $locale]));
            $this->get('/'.$locale.'/register')->assertOk()->assertSee(route('privacy', ['locale' => $locale]));
            $procedure = Procedure::factory()->create();
            $this->post('/'.$locale.'/leaflet', ['procedures' => [$procedure->id]])->assertRedirect('/'.$locale.'/leaflet/details');
            $this->get('/'.$locale.'/leaflet/details')->assertOk()->assertSee(route('privacy', ['locale' => $locale]));
        }
    }

    public function test_scheduler_registers_backup_and_retention_at_requested_times(): void
    {
        $events = collect(app(Schedule::class)->events());
        $this->assertTrue($events->contains(fn ($event) => str_contains($event->command ?? '', 'db:backup --keep=14') && $event->expression === '15 3 * * *'));
        $this->assertTrue($events->contains(fn ($event) => str_contains($event->command ?? '', 'leaflets:purge') && $event->expression === '30 3 * * *'));
    }
}
