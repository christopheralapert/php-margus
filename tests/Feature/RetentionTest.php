<?php

namespace Tests\Feature;

use App\Enums\ConsentPurpose;
use App\Models\Consent;
use App\Models\SavedLeaflet;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_uses_settings_and_last_activity_with_created_at_fallback(): void
    {
        app(Settings::class)->set('retention.saved_leaflet_months', 2);
        app(Settings::class)->set('retention.unverified_account_days', 10);
        $old = User::factory()->create(['created_at' => now()->subMonths(4), 'last_active_at' => now()->subMonths(3)]);
        $fallback = User::factory()->create(['created_at' => now()->subMonths(3), 'last_active_at' => null]);
        $active = User::factory()->create(['created_at' => now()->subYears(2), 'last_active_at' => now()]);
        $recent = User::factory()->create(['last_active_at' => null]);
        $expired = User::factory()->unverified()->create(['created_at' => now()->subDays(11)]);
        $unverified = User::factory()->unverified()->create(['created_at' => now()->subDays(9)]);
        foreach ([$old, $fallback, $active, $recent, $expired, $unverified] as $user) {
            SavedLeaflet::factory()->for($user)->create();
            Consent::factory()->for($user)->create();
        }
        DB::table('sessions')->insert([
            ['id' => 'expired', 'payload' => '', 'last_activity' => now()->subMinutes(config('session.lifetime') + 1)->timestamp],
            ['id' => 'current', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $this->artisan('leaflets:purge')->assertSuccessful();
        foreach ([$old, $fallback] as $user) {
            $this->assertNotNull($user->fresh());
            $this->assertSame(0, $user->savedLeaflets()->count());
        }
        foreach ([$active, $recent, $unverified] as $user) {
            $this->assertNotNull($user->fresh());
            $this->assertSame(1, $user->savedLeaflets()->count());
        }
        $this->assertNull($expired->fresh());
        $this->assertDatabaseMissing('saved_leaflets', ['user_id' => $expired->id]);
        $this->assertDatabaseMissing('consents', ['user_id' => $expired->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'expired']);
        $this->assertDatabaseHas('sessions', ['id' => 'current']);
    }

    public function test_withdrawn_consent_erases_account_or_personalisation_and_then_consent(): void
    {
        $account = User::factory()->create();
        $personalised = User::factory()->create();
        $other = SavedLeaflet::factory()->create();
        SavedLeaflet::factory()->for($account)->create();
        $leaflet = SavedLeaflet::factory()->for($personalised)->create();
        Consent::factory()->for($account)->create(['purpose' => ConsentPurpose::Account, 'withdrawn_at' => now()]);
        Consent::factory()->for($account)->create();
        $withdrawn = Consent::factory()->for($personalised)->create(['withdrawn_at' => now()]);
        $anonymous = Consent::factory()->create(['user_id' => null, 'withdrawn_at' => now()]);
        $chat = Consent::factory()->for($personalised)->create(['purpose' => ConsentPurpose::Chat, 'withdrawn_at' => now()]);
        $retained = Consent::factory()->for($personalised)->create(['purpose' => ConsentPurpose::Account]);

        $this->artisan('leaflets:purge')->assertSuccessful();
        $this->assertNull($account->fresh());
        $this->assertDatabaseMissing('saved_leaflets', ['user_id' => $account->id]);
        $this->assertDatabaseMissing('consents', ['user_id' => $account->id]);
        $this->assertNotNull($personalised->fresh());
        $this->assertNull($leaflet->fresh()->first_name);
        $this->assertNull($leaflet->fresh()->procedure_date);
        $this->assertSame($leaflet->procedure_ids, $leaflet->fresh()->procedure_ids);
        foreach ([$withdrawn, $anonymous, $chat] as $consent) {
            $this->assertNull($consent->fresh());
        }
        $this->assertNotNull($retained->fresh());
        $this->assertNotNull($other->fresh()->first_name);
    }

    public function test_dry_run_changes_nothing_and_output_contains_only_categories_and_counts(): void
    {
        $user = User::factory()->unverified()->create(['created_at' => now()->subYears(2)]);
        $leaflet = SavedLeaflet::factory()->for($user)->create();
        $consent = Consent::factory()->for($user)->create(['withdrawn_at' => now()]);
        DB::table('sessions')->insert(['id' => 'expired', 'payload' => '', 'last_activity' => 1]);
        Artisan::call('leaflets:purge', ['--dry-run' => true]);
        $output = Artisan::output();
        $this->assertStringNotContainsString($user->email, $output);
        $this->assertStringNotContainsString($leaflet->first_name, $output);
        $this->assertStringContainsString(__('privacy.purge.unverified_accounts').': 1', $output);
        $this->assertNotNull($user->fresh());
        $this->assertSame($leaflet->first_name, $leaflet->fresh()->first_name);
        $this->assertNotNull($consent->fresh());
        $this->assertDatabaseHas('sessions', ['id' => 'expired']);
        Artisan::call('leaflets:purge');
        $this->assertStringNotContainsString($user->email, Artisan::output());
    }
}
