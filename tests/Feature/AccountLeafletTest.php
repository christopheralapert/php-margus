<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountLeafletTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_explicit_consent_and_records_it_without_a_name(): void
    {
        Notification::fake();
        $credentials = ['email' => 'patient@example.com', 'password' => 'password', 'password_confirmation' => 'password'];
        $this->post('/en/register', $credentials)->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('consents', 0);
        $this->post('/en/register', $credentials + ['consent' => '0'])->assertSessionHasErrors('consent');
        $this->post('/en/register', $credentials + ['consent' => '1'])->assertRedirect('/en/dashboard');
        $user = User::sole();
        $this->assertNull($user->name);
        $this->assertSame('en', $user->locale);
        $this->assertNotNull($user->last_active_at);
        $this->assertDatabaseHas('consents', [
            'user_id' => $user->id, 'purpose' => 'account', 'locale' => 'en',
            'text_version' => config('leaflet.consent_version'), 'withdrawn_at' => null,
        ]);
        $this->assertNotNull($user->consents()->sole()->given_at);
        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $this->assertStringContainsString('/en/verify-email/', $mail->actionUrl);

            return true;
        });
        $this->get('/en/account/leaflets')->assertRedirect('/en/verify-email');
    }

    public function test_registration_name_is_optional_first_name_with_maximum_forty_characters(): void
    {
        foreach (['First Last', str_repeat('a', 41)] as $name) {
            $this->post('/et/register', [
                'name' => $name, 'email' => 'patient@example.com', 'password' => 'password',
                'password_confirmation' => 'password', 'consent' => '1',
            ])->assertSessionHasErrors('name');
        }
        $html = $this->get('/et/register')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*id="consent"[^>]*required/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*id="consent"[^>]*checked/', $html);
    }

    public function test_guest_can_log_in_from_preview_without_losing_wizard_data(): void
    {
        $procedure = Procedure::factory()->create();
        $user = User::factory()->create(['last_active_at' => now()->subDay()]);
        $wizard = ['procedures' => [$procedure->id], 'first_name' => 'Mari', 'date' => '2026-11-23', 'consent' => true];
        $this->withSession(['wizard' => $wizard])->get('/et/leaflet/preview')->assertOk()
            ->assertSee(__('account.login_to_save', [], 'et'))
            ->assertSee(route('login', ['save_leaflet' => '1']), false);
        $this->get('/et/login?save_leaflet=1')->assertOk()->assertSessionHas('url.intended', route('wizard.preview'));
        $this->post('/et/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/et/leaflet/preview')->assertSessionHas('wizard', $wizard);
        $this->assertTrue($user->fresh()->last_active_at->isToday());
        $this->get('/et/leaflet/preview')->assertOk()->assertSee(__('account.save', [], 'et'));
    }

    public static function consentStates(): array
    {
        return [[true], [false]];
    }

    #[DataProvider('consentStates')]
    public function test_save_preserves_order_and_session_and_encrypts_only_consented_details(bool $consent): void
    {
        $user = User::factory()->create();
        $ids = Procedure::factory()->count(2)->create()->modelKeys();
        $ids = array_reverse($ids);
        $wizard = ['procedures' => $ids, 'first_name' => 'Mari', 'date' => '2026-11-23', 'consent' => $consent];
        $this->actingAs($user)->withSession(['wizard' => $wizard])->post('/en/account/leaflets', [
            'first_name' => 'Untrusted', 'locale' => 'et',
        ])->assertRedirect('/en/account/leaflets')->assertSessionHas('wizard', $wizard)
            ->assertSessionHas('status', __('account.saved', [], 'en'));
        $leaflet = SavedLeaflet::sole();
        $this->assertSame($ids, $leaflet->procedure_ids);
        $this->assertSame('en', $leaflet->locale);
        $this->assertSame($user->id, $leaflet->user_id);
        $this->assertSame('a5', $leaflet->format->value);
        $this->assertSame($consent ? 'Mari' : null, $leaflet->first_name);
        $this->assertSame($consent ? '2026-11-23' : null, $leaflet->procedure_date);
        $raw = DB::table('saved_leaflets')->first();
        if ($consent) {
            $this->assertNotSame('Mari', $raw->first_name);
            $this->assertNotSame('2026-11-23', $raw->procedure_date);
        } else {
            $this->assertNull($raw->first_name);
            $this->assertNull($raw->procedure_date);
        }
    }

    public function test_list_uses_current_locale_shows_only_own_leaflets_newest_first_and_handles_removed_ids(): void
    {
        $user = User::factory()->create(['last_active_at' => now()->subDay()]);
        $first = Procedure::factory()->create(['name' => ['et' => 'Vanem ravi', 'en' => 'Older treatment']]);
        $second = Procedure::factory()->create(['name' => ['et' => 'Uuem ravi', 'en' => 'Newer treatment']]);
        $inactive = Procedure::factory()->create(['is_active' => false]);
        $old = SavedLeaflet::factory()->for($user)->create(['procedure_ids' => [$first->id], 'created_at' => now()->subDay()]);
        $new = SavedLeaflet::factory()->for($user)->create(['procedure_ids' => [$second->id, $inactive->id, 999999], 'locale' => 'et']);
        $other = SavedLeaflet::factory()->create();
        $this->actingAs($user)->get('/en/account/leaflets')->assertOk()
            ->assertSeeInOrder(['Newer treatment', 'Older treatment'])->assertDontSee('Uuem ravi')
            ->assertSee(__('account.languages.et', [], 'en'))->assertSee(__('account.removed', [], 'en'))
            ->assertSee($old->uuid)->assertSee($new->uuid)->assertDontSee($other->uuid);
        $this->assertTrue($user->fresh()->last_active_at->isToday());
    }

    public function test_download_regenerates_in_saved_locale_without_saving_pdf_and_updates_download_date(): void
    {
        $leaflet = SavedLeaflet::factory()->create(['locale' => 'et']);
        $before = collect(File::allFiles(storage_path()))->filter(fn ($file) => $file->getExtension() === 'pdf')->map->getPathname()->sort()->values()->all();
        $response = $this->actingAs($leaflet->user)->get('/en/account/leaflets/'.$leaflet->uuid.'/pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString(__('wizard.pdf_filename', [], 'et'), $response->headers->get('Content-Disposition'));
        $pdf = $response->streamedContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('/Lang (et)', $pdf);
        $this->assertNotNull($leaflet->fresh()->last_downloaded_at);
        $after = collect(File::allFiles(storage_path()))->filter(fn ($file) => $file->getExtension() === 'pdf')->map->getPathname()->sort()->values()->all();
        $this->assertSame($before, $after);
    }

    public function test_other_users_uuid_and_unknown_uuid_return_404(): void
    {
        $leaflet = SavedLeaflet::factory()->create();
        $this->actingAs(User::factory()->create());
        foreach ([$leaflet->uuid, '00000000-0000-0000-0000-000000000000'] as $uuid) {
            $this->get('/et/account/leaflets/'.$uuid.'/pdf')->assertNotFound();
            $this->delete('/et/account/leaflets/'.$uuid, ['confirm' => '1'])->assertNotFound();
            $this->delete('/et/account/leaflets/'.$uuid)->assertNotFound();
        }
        $this->assertModelExists($leaflet);
        $this->assertNull($leaflet->fresh()->last_downloaded_at);
    }

    public function test_delete_requires_confirmation_then_removes_the_row(): void
    {
        $leaflet = SavedLeaflet::factory()->create();
        $this->actingAs($leaflet->user)->from('/et/account/leaflets')->delete('/et/account/leaflets/'.$leaflet->uuid)
            ->assertRedirect('/et/account/leaflets')->assertSessionHasErrors('confirm');
        $this->assertModelExists($leaflet);
        $this->delete('/et/account/leaflets/'.$leaflet->uuid, ['confirm' => '1'])->assertRedirect('/et/account/leaflets');
        $this->assertModelMissing($leaflet);
    }

    public function test_empty_session_and_empty_account_and_unavailable_download_are_handled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/et/account/leaflets')->assertOk()->assertSee(__('account.empty', [], 'et'))->assertSee(route('wizard.procedures'), false);
        $this->post('/et/account/leaflets')->assertRedirect('/et/leaflet');
        $inactive = Procedure::factory()->create(['is_active' => false]);
        $leaflet = SavedLeaflet::factory()->for($user)->create(['procedure_ids' => [$inactive->id, 999999]]);
        $this->get('/et/account/leaflets/'.$leaflet->uuid.'/pdf')->assertRedirect('/et/account/leaflets')
            ->assertSessionHas('status', __('account.unavailable', [], 'et'));
        $this->assertNull($leaflet->fresh()->last_downloaded_at);
    }

    public function test_unverified_users_cannot_use_any_account_action(): void
    {
        $user = User::factory()->unverified()->create();
        $leaflet = SavedLeaflet::factory()->for($user)->create();
        $this->actingAs($user);
        $this->get('/en/account/leaflets')->assertRedirect('/en/verify-email');
        $this->post('/en/account/leaflets')->assertRedirect('/en/verify-email');
        $this->get('/en/account/leaflets/'.$leaflet->uuid.'/pdf')->assertRedirect('/en/verify-email');
        $this->delete('/en/account/leaflets/'.$leaflet->uuid, ['confirm' => '1'])->assertRedirect('/en/verify-email');
    }
}
