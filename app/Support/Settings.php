<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Schema;

/**
 * Phone settings are display strings; derive tel values with str_replace(' ', '', $phone).
 * A null clinic.website means consumers should use config('app.url').
 */
final class Settings
{
    public function __construct(private readonly Repository $cache) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $fallback = config('clinic.'.$key, $default);

        // Before migrations, even the default database cache table may not exist.
        if (! Schema::hasTable('settings')) {
            return $fallback;
        }

        $settings = $this->cache->rememberForever('settings.all', fn (): array => Setting::all()->pluck('value', 'key')->all());

        return array_key_exists($key, $settings) ? $settings[$key] : $fallback;
    }

    public function localized(string $key, ?string $locale = null): ?string
    {
        $value = $this->get($key);

        if (! is_array($value)) {
            return is_string($value) ? $value : null;
        }

        $localized = $value[$locale ?? app()->getLocale()] ?? $value[config('app.fallback_locale')] ?? null;

        return is_string($localized) ? $localized : null;
    }

    public function set(string $key, mixed $value): void
    {
        // Encode explicitly so PHP null is JSON null, not SQL NULL (value is NOT NULL).
        Setting::upsert([
            ['key' => $key, 'value' => json_encode($value, JSON_THROW_ON_ERROR)],
        ], ['key'], ['value', 'updated_at']);
        $this->flush();
    }

    public function flush(): void
    {
        $this->cache->forget('settings.all');
    }
}
