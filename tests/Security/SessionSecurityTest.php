<?php

namespace Tests\Security;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;

final class SessionSecurityTest extends SecurityTestCase
{
    public function test_every_state_changing_web_route_requires_csrf(): void
    {
        // Disable Laravel's testing-only CSRF bypass while keeping the real web stack.
        $this->app->instance('env', 'production');
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => in_array('web', $route->middleware(), true));
        $checked = 0;
        foreach ($routes as $route) {
            foreach (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) as $method) {
                $uri = '/'.preg_replace('/\{[^}]+\}/', '1', str_replace('{locale}', 'en', $route->uri()));
                $this->call($method, $uri)->assertStatus(419);
                $checked++;
            }
        }
        $this->assertGreaterThan(20, $checked);
        $this->assertSame(0, $this->llm->calls);
    }

    public static function unsafeIntendedUrls(): array
    {
        return array_map(fn ($url) => [$url], ['https://other.invalid/path', '//other.invalid/path', '/%2fother.invalid', '/%5cother.invalid', 'https://localhost@other.invalid', "https://other.invalid\r\nX-Test: bad", ['invalid']]);
    }

    #[DataProvider('unsafeIntendedUrls')]
    public function test_login_cannot_redirect_to_an_external_intended_url(mixed $url): void
    {
        $user = User::factory()->create();
        $this->withSession(['url.intended' => $url])->post('/en/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/en/dashboard')->assertSessionMissing('url.intended');
    }

    public function test_password_reset_has_same_response_for_known_unknown_and_throttled_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        foreach ([$user->email, 'unknown@example.test', $user->email] as $email) {
            $this->from('/en/forgot-password')->post('/en/forgot-password', ['email' => $email])->assertRedirect('/en/forgot-password')
                ->assertSessionHasNoErrors()->assertSessionMissing('_old_input')->assertSessionHas('status', __('passwords.requested', [], 'en'));
        }
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_production_session_cookie_is_secure_http_only_and_same_site(): void
    {
        // Load the actual production configuration, removing test/local env overrides.
        $names = ['APP_ENV', 'SESSION_SECURE_COOKIE', 'SESSION_HTTP_ONLY', 'SESSION_SAME_SITE'];
        $previous = [];
        foreach ($names as $name) {
            $previous[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)];
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
        }
        $_ENV['APP_ENV'] = 'production';
        try {
            $session = require base_path('config/session.php');
        } finally {
            foreach ($previous as $name => [$env, $server, $process]) {
                unset($_ENV[$name], $_SERVER[$name]);
                if ($env !== null) {
                    $_ENV[$name] = $env;
                }
                if ($server !== null) {
                    $_SERVER[$name] = $server;
                }
                $process === false ? putenv($name) : putenv($name.'='.$process);
            }
        }
        $this->assertTrue($session['secure']);
        $this->assertTrue($session['http_only']);
        $this->assertSame('lax', $session['same_site']);
        config(['session.secure' => $session['secure'], 'session.http_only' => $session['http_only'], 'session.same_site' => $session['same_site']]);
        $cookies = $this->get('/en')->assertOk()->headers->getCookies();
        $cookie = collect($cookies)->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }
}
