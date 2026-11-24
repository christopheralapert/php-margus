<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Setting;
use App\Services\Leaflet\ThemeColor;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_upsert_all_defaults_and_categories(): void
    {
        $keys = [
            'clinic.name', 'clinic.phone', 'clinic.after_hours_phone', 'clinic.emergency',
            'clinic.email', 'clinic.address', 'clinic.hours', 'clinic.website',
            'brand.logo_path', 'brand.colors', 'leaflet.disclaimer',
            'retention.saved_leaflet_months', 'retention.unverified_account_days',
        ];
        $slugs = ['dental', 'dermatology', 'aesthetic', 'eye', 'ent', 'surgery', 'orthopaedics', 'gynaecology', 'vaccination'];
        $settings = app(Settings::class);
        $settings->get('brand.colors'); // Prime the empty-table cache before seeding.
        $this->seed(DatabaseSeeder::class);
        $categoryIds = Category::orderBy('sort')->get()->modelKeys();
        $settingIds = Setting::orderBy('key')->pluck('id')->all();
        $settings->set('clinic.phone', 'changed');
        Category::where('slug', 'dental')->update(['color' => '#000000']);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('settings', count($keys));
        $this->assertDatabaseCount('categories', 9);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame($categoryIds, Category::orderBy('sort')->get()->modelKeys());
        $this->assertSame($settingIds, Setting::orderBy('key')->pluck('id')->all());
        $this->assertEqualsCanonicalizing($keys, Setting::pluck('key')->all());
        $this->assertSame($slugs, Category::orderBy('sort')->pluck('slug')->all());
        $this->assertSame([
            '#2E6FD8', '#E2725B', '#D9488F', '#2BB3A3', '#7C5CD6',
            '#3B8C5A', '#F08A24', '#A23E9C', '#0EA5C6',
        ], Category::orderBy('sort')->pluck('color')->all());

        foreach ($keys as $key) {
            $this->assertSame(config('clinic.'.$key), $settings->get($key));
        }

        foreach (Category::orderBy('sort')->get() as $sort => $category) {
            $this->assertNotEmpty($category->getTranslations('name')['et']);
            $this->assertNotEmpty($category->getTranslations('name')['en']);
            $this->assertSame($sort, $category->sort);
            $this->assertTrue($category->is_active);
            $this->assertSame('icons/categories/'.$category->slug.'.svg', $category->icon_path);
            $this->assertFileExists(public_path($category->icon_path));
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $category->color);
        }

        foreach ([...Category::pluck('color')->all(), ...array_values($settings->get('brand.colors'))] as $base) {
            $theme = new ThemeColor($base);
            $this->assertGreaterThanOrEqual(4.5, ThemeColor::contrast($theme->dark(), '#FFFFFF'));
            if (ThemeColor::contrast($base, '#FFFFFF') >= 4.5) {
                $this->assertSame($base, $theme->dark());
            }
        }
    }

    public function test_brand_assets_are_safe_self_contained_svg(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['brand/logo.svg', 'brand/logo-mark.svg', ...Category::pluck('icon_path')->all()] as $path) {
            $this->assertFileExists(public_path($path));
            $document = new DOMDocument;
            $this->assertTrue($document->load(public_path($path), LIBXML_NONET));
            $this->assertSame('svg', $document->documentElement->localName);
            $xpath = new DOMXPath($document);
            $this->assertSame(0, $xpath->query('//*[local-name()="script" or local-name()="text" or local-name()="style" or local-name()="foreignObject"]')->length);
            $this->assertSame(0, $xpath->query('//@*[local-name()="href"]')->length);

            if (str_starts_with($path, 'icons/')) {
                $this->assertSame('0 0 24 24', $document->documentElement->getAttribute('viewBox'));
                $this->assertSame('currentColor', $document->documentElement->getAttribute('stroke'));
                $this->assertSame('round', $document->documentElement->getAttribute('stroke-linejoin'));
            }
        }
    }
}
