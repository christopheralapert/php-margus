<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render_with_headers_and_no_unprotected_inline_scripts(): void
    {
        // Render the real Vite tags, rather than the base test case's empty fake.
        $this->swap(\Illuminate\Foundation\Vite::class, new \Illuminate\Foundation\Vite);
        config(['chat.enabled' => true]);
        $procedure = Procedure::factory()->withContent()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $leaflet = SavedLeaflet::factory()->for($admin)->create(['procedure_ids' => [$procedure->id]]);

        foreach (['et', 'en'] as $locale) {
            $this->app['auth']->forgetGuards();
            foreach (['', '/privacy', '/login', '/register', '/forgot-password', '/reset-password/example', '/leaflet', '/leaflet/details', '/leaflet/preview', '/l/'.$procedure->slug] as $path) {
                $response = $this->withSession(['wizard' => ['procedures' => [$procedure->id]]])->get('/'.$locale.$path)->assertOk();
                $this->assertHtmlSecurity($response);
            }
            $this->actingAs($admin);
            foreach (['/dashboard', '/profile', '/verify-email', '/confirm-password', '/account/leaflets', '/admin', '/admin/categories', '/admin/categories/create', '/admin/categories/'.$procedure->category_id.'/edit', '/admin/procedures', '/admin/procedures/create', '/admin/procedures/'.$procedure->id.'/edit', '/admin/settings'] as $path) {
                $response = $this->get('/'.$locale.$path);
                // Verified users are redirected away from the verification notice.
                if ($path === '/verify-email') {
                    $response->assertRedirect();
                } else {
                    $response->assertOk();
                    $this->assertHtmlSecurity($response);
                }
            }
        }

        $pdf = $this->withSession(['wizard' => ['procedures' => [$procedure->id]]])->post('/en/leaflet/pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $pdf->assertHeader('Content-Security-Policy');
        $this->get('/en/admin/procedures/'.$procedure->id.'/preview.pdf')->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Security-Policy')
            ->assertHeader('Content-Disposition', 'inline; filename="preview-'.$procedure->slug.'-en.pdf"');
        $this->get('/en/account/leaflets/'.$leaflet->uuid.'/pdf')->assertOk()->assertHeader('Content-Security-Policy');
    }

    private function assertHtmlSecurity($response): void
    {
        $response->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $policy = $response->headers->get('Content-Security-Policy');
        foreach (["default-src 'self'", "script-src 'self' 'unsafe-eval'", "img-src 'self' data:", "connect-src 'self'", "frame-ancestors 'none'", "object-src 'none'", "form-action 'self'", "base-uri 'self'"] as $directive) {
            $this->assertStringContainsString($directive, $policy);
        }
        $this->assertDoesNotMatchRegularExpression('/script-src[^;]*\x27unsafe-inline\x27/', $policy);
        $dom = new DOMDocument;
        @$dom->loadHTML($response->getContent());
        foreach ($dom->getElementsByTagName('script') as $script) {
            if (! $script->hasAttribute('src')) {
                $this->assertNotSame('', $script->getAttribute('nonce'));
                $this->assertStringContainsString("'nonce-".$script->getAttribute('nonce')."'", $policy);
            }
        }
    }

    public static function environments(): array
    {
        return [['production', 'https', true], ['production', 'http', false], ['local', 'https', false], ['testing', 'https', false]];
    }

    #[DataProvider('environments')]
    public function test_hsts_requires_production_and_https(string $environment, string $scheme, bool $expected): void
    {
        $this->app->instance('env', $environment);
        $response = $this->get($scheme.'://localhost/en')->assertOk();
        if ($expected) {
            $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        } else {
            $response->assertHeaderMissing('Strict-Transport-Security');
        }
    }

    public function test_session_cookie_flags(): void
    {
        config(['session.secure' => true]);
        $response = $this->get('https://localhost/en')->assertOk();
        $cookies = collect($response->headers->getCookies());
        $cookie = $cookies->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_vite_hot_origin_is_only_allowed_locally_with_a_hot_file(): void
    {
        $path = storage_path('framework/testing-security.hot');
        $vite = new \Illuminate\Foundation\Vite;
        $vite->useHotFile($path);
        $this->swap(\Illuminate\Foundation\Vite::class, $vite);
        $origin = 'http://localhost:5173';
        try {
            file_put_contents($path, $origin);
            foreach (['local', 'production'] as $environment) {
                $this->app->instance('env', $environment);
                $response = $this->get('/en')->assertOk();
                $policy = $response->headers->get('Content-Security-Policy');
                if ($environment === 'local') {
                    $this->assertStringContainsString($origin, $policy);
                    $this->assertStringContainsString('ws://localhost:5173', $policy);
                    $this->assertStringContainsString('nonce="'.Vite::cspNonce().'"', $response->getContent());
                } else {
                    $this->assertStringNotContainsString($origin, $policy);
                }
            }
            unlink($path);
            $this->app->instance('env', 'local');
            $this->assertStringNotContainsString($origin, $this->get('/en')->headers->get('Content-Security-Policy'));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_every_admin_route_requires_authentication_verification_and_admin_gate(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (str_starts_with($route->getName() ?? '', 'admin.')) {
                foreach (['auth', 'verified', 'can:admin'] as $middleware) {
                    $this->assertContains($middleware, $route->gatherMiddleware(), $route->getName());
                }
            }
        }
    }

    public function test_category_icon_is_sanitized_again_at_display(): void
    {
        $path = public_path('icons/categories/security-review.svg');
        try {
            file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"><script>bad()</script><path d="M0 0h4" onload="bad()"/></svg>');
            $html = view('components.category-icon', ['slug' => 'security-review', 'color' => '#123456'])->render();
            $this->assertStringNotContainsString('bad()', $html);
            $this->assertStringContainsString('M0 0h4', $html);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
