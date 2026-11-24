<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Chat\AnthropicLlmClient;
use App\Services\Chat\ChatUnavailable;
use App\Services\Chat\FakeLlmClient;
use App\Services\Chat\LlmClient;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->bind(LlmClient::class, fn () => match (config('chat.provider')) {
            'fake' => new FakeLlmClient,
            'anthropic' => new AnthropicLlmClient,
            default => throw new ChatUnavailable,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', fn (User $u) => $u->is_admin);

        // SQLite pragmas are applied to every connection by config/database.php.
        RateLimiter::for('chat', function (Request $request): array {
            if (! config('chat.enabled')) {
                return [Limit::none()];
            }

            $response = fn (Request $request, array $headers) => response()->json([
                'message' => __('chat.rate_limit', [
                    'phone' => app(Settings::class)->get('clinic.phone'),
                    'emergency' => app(Settings::class)->get('clinic.emergency'),
                ]),
            ], 429, $headers);

            return [
                Limit::perMinutes(10, 8)->by('chat:session:'.$request->session()->getId())->response($response),
                Limit::perDay(30)->by('chat:ip:'.hash('sha256', $request->ip().config('app.key')))->response($response),
            ];
        });
    }
}
