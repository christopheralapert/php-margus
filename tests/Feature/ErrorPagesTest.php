<?php

namespace Tests\Feature;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public static function statuses(): array
    {
        $cases = [];
        foreach (['et', 'en'] as $locale) {
            foreach ([403, 404, 419, 429, 500, 503] as $status) {
                $cases[] = [$locale, $status];
            }
        }

        return $cases;
    }

    #[DataProvider('statuses')]
    public function test_errors_render_without_a_database(string $locale, int $status): void
    {
        config(['app.debug' => false, 'database.connections.sqlite.database' => '/unavailable/clinic.sqlite']);
        if ($status !== 404) {
            // No production route is added for triggering errors.
            Route::get('/'.$locale.'/forced-error', function () use ($status): void {
                if ($status === 500) {
                    throw new RuntimeException('Internal failure');
                }
                abort($status);
            });
        }

        $this->get('/'.$locale.($status === 404 ? '/unknown' : '/forced-error'))
            ->assertStatus($status)
            ->assertSee('<html lang="'.$locale.'">', false)
            ->assertSee(__('errors.'.$status.'.title', [], $locale))
            ->assertSee(config('clinic.clinic.phone'))
            ->assertSee('href="'.route('home', ['locale' => $locale]).'"', false)
            ->assertSee('href="#main"', false);
    }

    public function test_unknown_routes_prefer_the_url_then_encrypted_cookie_then_estonian(): void
    {
        config(['app.debug' => false]);
        $this->withCookie('locale', 'en')->get('/unknown')->assertNotFound()->assertSee('<html lang="en">', false);
        $this->withCookie('locale', 'en')->get('/et/unknown')->assertNotFound()->assertSee('<html lang="et">', false);
        $this->withCookie('locale', 'invalid')->get('/unknown')->assertNotFound()->assertSee('<html lang="et">', false);
        $encrypter = app('encrypter');
        $encrypted = $encrypter->encrypt(CookieValuePrefix::create('locale', $encrypter->getKey()).'en', false);
        $this->withUnencryptedCookie('locale', $encrypted)->get('/unknown')->assertNotFound()->assertSee('<html lang="en">', false);
    }
}
