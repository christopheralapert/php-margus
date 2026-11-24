<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_work_with_an_empty_table_and_missing_tables(): void
    {
        $settings = app(Settings::class);
        $this->assertSame($settings, app(Settings::class));
        $this->assertSame(config('clinic.clinic.name'), $settings->get('clinic.name'));
        $this->assertSame(config('clinic.brand.colors'), $settings->get('brand.colors'));
        $this->assertSame('default', $settings->get('unknown', 'default'));
        $this->assertNull($settings->get('clinic.website', 'default'));

        Schema::drop('settings');
        Schema::drop('cache');
        config(['cache.default' => 'database']);
        $this->assertSame(config('clinic.clinic.phone'), $settings->get('clinic.phone'));
    }

    public function test_localized_uses_current_explicit_and_fallback_locales(): void
    {
        $setting = Setting::factory()->create();
        $settings = app(Settings::class);

        foreach (['et', 'en'] as $locale) {
            app()->setLocale($locale);
            $this->assertSame($setting->value[$locale], $settings->localized($setting->key));
            $this->assertSame(config('clinic.clinic.name.'.$locale), $settings->localized('clinic.name', $locale));
        }

        config(['app.fallback_locale' => 'et']);
        $this->assertSame($setting->value['et'], $settings->localized($setting->key, 'fr'));
        $settings->set($setting->key, ['et' => $setting->value['et']]);
        $this->assertSame($setting->value['et'], $settings->localized($setting->key, 'en'));
        $this->assertNull($settings->localized('unknown'));
        $this->assertNull($settings->localized('brand.colors'));
        $this->assertNull($settings->localized('retention.saved_leaflet_months'));
        $this->assertSame(config('clinic.clinic.phone'), $settings->localized('clinic.phone'));
    }

    public function test_settings_are_cached_until_set_or_flush(): void
    {
        $setting = Setting::factory()->create();
        $settings = app(Settings::class);
        $this->assertSame($setting->value, $settings->get($setting->key));
        $this->assertTrue(Cache::has('settings.all'));

        $setting->update(['value' => false]);
        $this->assertIsArray($settings->get($setting->key));
        $settings->flush();
        $this->assertFalse(Cache::has('settings.all'));
        $this->assertFalse($settings->get($setting->key));

        $settings->set($setting->key, null);
        $this->assertFalse(Cache::has('settings.all'));
        $this->assertNull($settings->get($setting->key, 'fallback'));
        $settings->set($setting->key, 12);
        $this->assertSame(12, $settings->get($setting->key));
        $this->assertSame(12, $setting->fresh()->value);
        $this->assertDatabaseCount('settings', 1);
    }
}
