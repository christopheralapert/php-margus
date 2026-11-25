<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeIntendedUrlTest extends TestCase
{
    use RefreshDatabase;

    public static function unsafeUrls(): array
    {
        return array_map(fn ($url) => [$url], [
            'https://other.test/en', '//other.test/en', '/\\other.test/en', 'javascript:alert(1)',
            '/%2fother.test/en', '/%5cother.test/en', 'https://localhost@other.test/en', ['unexpected'],
        ]);
    }

    #[DataProvider('unsafeUrls')]
    public function test_login_discards_external_or_malformed_intended_urls(mixed $url): void
    {
        $user = User::factory()->create();
        $this->withSession(['url.intended' => $url])->post('/en/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/en/dashboard');
    }

    public function test_login_preserves_same_host_intended_urls(): void
    {
        $user = User::factory()->create();
        $this->withSession(['url.intended' => url('/en/leaflet/preview')])
            ->post('/en/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(url('/en/leaflet/preview'));
    }
}
