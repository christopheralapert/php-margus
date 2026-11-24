<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\SavedLeaflet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/en/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/en/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/en/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/en/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/en/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();
        $leaflet = SavedLeaflet::factory()->for($user)->create();
        $consent = Consent::factory()->for($user)->create();
        $other = SavedLeaflet::factory()->create();
        DB::table('sessions')->insert(['id' => 'other-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp]);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'token', 'created_at' => now()]);

        $response = $this
            ->actingAs($user)
            ->delete('/en/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('privacy.deleted', [], 'en'))
            ->assertRedirect('/en');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertNull($leaflet->fresh());
        $this->assertNull($consent->fresh());
        $this->assertNotNull($other->fresh());
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();
        $leaflet = SavedLeaflet::factory()->for($user)->create();
        $consent = Consent::factory()->for($user)->create();

        $response = $this
            ->actingAs($user)
            ->from('/en/profile')
            ->delete('/en/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/en/profile');

        $this->assertNotNull($user->fresh());
        $this->assertNotNull($leaflet->fresh());
        $this->assertNotNull($consent->fresh());
        $this->assertAuthenticatedAs($user);
    }

    public function test_estonian_deletion_clears_session_and_flashes_localised_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['wizard.first_name' => 'Private', '_token' => 'old-token'])
            ->delete('/et/profile', ['password' => 'password'])
            ->assertRedirect('/et')
            ->assertSessionMissing('wizard.first_name')
            ->assertSessionHas('status', __('privacy.deleted', [], 'et'));
        $this->assertNotSame('old-token', session()->token());
        $this->assertGuest();
        $this->get('/et')->assertSee(__('privacy.deleted', [], 'et'));
    }
}
