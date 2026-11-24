<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_grants_and_revokes_admin_access(): void
    {
        $user = User::factory()->create();
        $this->artisan('app:make-admin', ['email' => $user->email])
            ->expectsOutput(__('admin.command.granted'))->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('app:make-admin', ['email' => $user->email, '--revoke' => true])
            ->expectsOutput(__('admin.command.revoked'))->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_missing_user_returns_error_without_creating_an_account(): void
    {
        $this->artisan('app:make-admin', ['email' => 'missing@example.test'])
            ->expectsOutput(__('admin.command.not_found'))->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
