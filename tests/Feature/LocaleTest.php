<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_public_pages_render_in_the_requested_language(string $locale): void
    {
        foreach (['' => 'app.title', '/login' => 'app.log_in', '/register' => 'app.register', '/forgot-password' => 'app.email_reset_link', '/reset-password/token' => 'app.reset_password'] as $path => $key) {
            $this->get('/'.$locale.$path)
                ->assertOk()
                ->assertSee('lang="'.$locale.'"', false)
                ->assertSee(__($key, [], $locale));
        }
    }

    #[DataProvider('locales')]
    public function test_authenticated_pages_render_in_the_requested_language(string $locale): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['dashboard' => 'app.dashboard', 'profile' => 'app.profile_information', 'confirm-password' => 'app.confirm_intro'] as $path => $key) {
            $this->get('/'.$locale.'/'.$path)
                ->assertOk()
                ->assertSee('lang="'.$locale.'"', false)
                ->assertSee(__($key, [], $locale))
                ->assertSee('hreflang="et"', false)
                ->assertSee('hreflang="en"', false);
        }
    }

    public function test_unsupported_locales_are_not_found(): void
    {
        foreach (['', '/login', '/register', '/dashboard', '/verify-email', '/reset-password/token'] as $path) {
            $this->get('/xx'.$path)->assertNotFound();
        }

        $this->post('/xx/login')->assertNotFound();
    }

    public function test_root_chooses_estonian_without_a_language_preference(): void
    {
        $this->withHeader('Accept-Language', '')->get('/')
            ->assertStatus(302)->assertRedirect('/et');
    }

    public function test_root_respects_language_preferences_and_supported_cookies(): void
    {
        $this->withHeader('Accept-Language', 'en-GB,en;q=0.9,et;q=0.5')
            ->get('/')->assertRedirect('/en');
        $this->withHeader('Accept-Language', 'et;q=0.9,en;q=0.5')
            ->get('/')->assertRedirect('/et');
        $this->withHeader('Accept-Language', 'fr')->get('/')->assertRedirect('/et');
        $this->withCookie('locale', 'en')->get('/')->assertRedirect('/en');
        $this->withHeader('Accept-Language', 'en')->withCookie('locale', 'et')
            ->get('/')->assertRedirect('/et');
        $this->withCookie('locale', 'xx')->get('/')->assertRedirect('/en');
    }

    public function test_legacy_paths_redirect_to_estonian(): void
    {
        foreach (['login', 'register', 'dashboard'] as $path) {
            $this->get('/'.$path)->assertStatus(302)->assertRedirect('/et/'.$path);
        }
    }

    #[DataProvider('locales')]
    public function test_auth_and_guest_redirects_keep_the_current_locale(string $locale): void
    {
        $this->get('/'.$locale.'/dashboard')->assertRedirect('/'.$locale.'/login');
        $this->get('/'.$locale.'/profile')->assertRedirect('/'.$locale.'/login');
        $this->actingAs(User::factory()->create())
            ->get('/'.$locale.'/login')->assertRedirect('/'.$locale.'/dashboard');
        $this->get('/'.$locale.'/register')->assertRedirect('/'.$locale.'/dashboard');
        $this->post('/'.$locale.'/logout')->assertRedirect('/'.$locale);
    }

    public function test_middleware_sets_url_defaults_and_removes_only_the_locale_parameter(): void
    {
        Route::get('/{locale}/locale-test/{token}', function (string $token) {
            $this->assertNull(request()->route('locale'));

            return response()->json(['login' => route('login'), 'token' => $token]);
        })->whereIn('locale', config('app.supported_locales'))->middleware(['web', SetLocale::class]);

        $this->get('/en/locale-test/example')
            ->assertOk()->assertJson(['login' => url('/en/login'), 'token' => 'example']);
        $this->get('/et/locale-test/second')
            ->assertOk()->assertJson(['login' => url('/et/login'), 'token' => 'second']);
    }

    public function test_locale_cookie_lasts_one_year_and_uses_same_site_lax(): void
    {
        $response = $this->get('/en/login')->assertCookie('locale', 'en');
        $cookie = $response->getCookie('locale');

        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame('/', $cookie->getPath());
        $this->assertEqualsWithDelta(now()->addDays(365)->getTimestamp(), $cookie->getExpiresTime(), 5);
    }

    public function test_language_switcher_changes_only_the_first_segment_and_keeps_the_query(): void
    {
        $response = $this->get('/en/login?next=%2Fen%2Fprofile&tag=en');
        $response->assertSee('href="'.url('/et/login').'?next=%2Fen%2Fprofile&amp;tag=en"', false)
            ->assertSee('aria-label="Language"', false)
            ->assertSee('hreflang="et" lang="et"', false)
            ->assertSee('hreflang="en" lang="en"', false)
            ->assertSee('Eesti')->assertSee('English');
        $this->assertMatchesRegularExpression('/hreflang="en" lang="en"\s+aria-current="true"/', $response->getContent());
        $this->get('/et')->assertSee('href="'.url('/en').'"', false)
            ->assertSee('aria-label="'.__('app.language', [], 'et').'"', false);
    }

    #[DataProvider('locales')]
    public function test_verification_notification_has_a_working_localized_signed_url(string $locale): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/'.$locale.'/verify-email')
            ->assertOk()->assertSee('lang="'.$locale.'"', false)
            ->assertSee(__('app.verify_intro', [], $locale));

        $mail = (new VerifyEmail)->toMail($user);
        $this->assertSame(__('Verify your email address', [], $locale), $mail->subject);
        $this->assertSame(__('Please click the button below to verify your email address.', [], $locale), $mail->introLines[0]);
        $this->assertStringStartsWith(url('/'.$locale.'/verify-email/'.$user->id.'/'), $mail->actionUrl);
        $this->assertStringContainsString(__('Hello!', [], $locale), (string) $mail->render());

        $this->get($mail->actionUrl)->assertRedirect('/'.$locale.'/dashboard?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    #[DataProvider('locales')]
    public function test_password_reset_notification_url_and_submission_keep_the_locale(string $locale): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/'.$locale.'/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user, $locale) {
            $mail = $notification->toMail($user);
            $this->assertSame(__('Reset your password', [], $locale), $mail->subject);
            $this->assertSame(__('You are receiving this email because we received a password reset request for your account.', [], $locale), $mail->introLines[0]);
            $this->assertStringStartsWith(url('/'.$locale.'/reset-password/'.$notification->token), $mail->actionUrl);
            $this->assertStringContainsString(__('All rights reserved.', [], $locale), (string) $mail->render());
            $this->get($mail->actionUrl)->assertOk()->assertSee('lang="'.$locale.'"', false);
            $this->post('/'.$locale.'/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasNoErrors()->assertRedirect('/'.$locale.'/login');

            return true;
        });
    }
}
