<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_admin_is_created_and_updated_without_printing_credentials(): void
    {
        $this->withEnvironment(['DEMO_ADMIN_EMAIL' => 'demo@example.com', 'DEMO_ADMIN_PASSWORD' => 'first-secret'], function (): void {
            $this->artisan('app:demo-admin')->expectsOutput(__('demo.admin_ready'))->assertSuccessful();
            $user = User::where('email', 'demo@example.com')->sole();
            $this->assertTrue($user->isAdmin());
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertTrue(Hash::check('first-secret', $user->password));

            $user->forceFill(['is_admin' => false, 'email_verified_at' => null])->save();
            $this->withEnvironment(['DEMO_ADMIN_PASSWORD' => 'updated-secret'], function (): void {
                $this->artisan('app:demo-admin')->expectsOutput(__('demo.admin_ready'))->assertSuccessful();
            });

            $this->assertDatabaseCount('users', 1);
            $this->assertTrue($user->refresh()->isAdmin());
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertTrue(Hash::check('updated-secret', $user->password));
        });
    }

    public static function missingCredentials(): array
    {
        return [['', ''], ['demo@example.com', ''], ['', 'secret'], ['invalid-email', 'secret']];
    }

    #[DataProvider('missingCredentials')]
    public function test_demo_admin_refuses_missing_or_invalid_credentials(string $email, string $password): void
    {
        $this->withEnvironment(['DEMO_ADMIN_EMAIL' => $email, 'DEMO_ADMIN_PASSWORD' => $password], function (): void {
            $this->artisan('app:demo-admin')->expectsOutput(__('demo.admin_missing'))->assertFailed();
            $this->assertDatabaseCount('users', 0);
        });
    }

    public static function demoModes(): array
    {
        return [[false, 'et'], [false, 'en'], [true, 'et'], [true, 'en']];
    }

    #[DataProvider('demoModes')]
    public function test_banner_is_localized_and_only_shown_in_demo_mode(bool $enabled, string $locale): void
    {
        config(['app.demo_mode' => $enabled]);
        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['', '/leaflet', '/privacy', '/login', '/register'] as $path) {
            $response = $this->get('/'.$locale.$path)->assertOk();
            $enabled
                ? $response->assertSee(__('demo.banner', [], $locale))
                : $response->assertDontSee(__('demo.banner', [], $locale));
        }

        foreach (['/admin', '/dashboard', '/account/leaflets'] as $path) {
            $response = $this->actingAs($admin)->get('/'.$locale.$path)->assertOk();
            $enabled
                ? $response->assertSee(__('demo.banner', [], $locale))
                : $response->assertDontSee(__('demo.banner', [], $locale));
        }
    }

    #[DataProvider('demoModes')]
    public function test_registration_only_auto_verifies_in_demo_mode(bool $enabled, string $locale): void
    {
        config(['app.demo_mode' => $enabled]);
        Notification::fake();
        $user = User::factory()->make();

        $this->post('/'.$locale.'/register', [
            'email' => $user->email,
            'password' => 'test-password',
            'password_confirmation' => 'test-password',
            'consent' => '1',
        ])->assertRedirect();

        $registered = User::where('email', $user->email)->sole();
        $this->assertSame($enabled, $registered->hasVerifiedEmail());
        if ($enabled) {
            Notification::assertNothingSent();
            $this->get('/'.$locale.'/account/leaflets')->assertOk();
        } else {
            Notification::assertSentTo($registered, VerifyEmail::class);
            $this->get('/'.$locale.'/account/leaflets')->assertRedirect(route('verification.notice'));
        }
    }

    public static function proxySettings(): array
    {
        return [[null, 'http'], ['*', 'https'], ['192.0.2.10', 'https'], ['192.0.2.11', 'http']];
    }

    #[DataProvider('proxySettings')]
    public function test_trusted_proxies_reads_environment_and_controls_forwarded_https(?string $proxies, string $scheme): void
    {
        $this->withEnvironment(['TRUSTED_PROXIES' => $proxies], function () use ($scheme): void {
            TrustProxies::flushState();
            $this->refreshApplication();
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
                ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443', 'Accept-Language' => 'et'])
                ->get('http://localhost/')->assertRedirect($scheme.'://localhost/et');
        });
        TrustProxies::flushState();
    }

    private function withEnvironment(array $values, callable $callback): void
    {
        $repository = Env::getRepository();
        $previous = [];
        foreach ($values as $key => $value) {
            $previous[$key] = $repository->get($key);
            $value === null ? $repository->clear($key) : $repository->set($key, $value);
        }

        try {
            $callback();
        } finally {
            foreach ($previous as $key => $value) {
                $value === null ? $repository->clear($key) : $repository->set($key, $value);
            }
        }
    }
}
