<?php

namespace Tests\Feature\Admin;

use App\Models\Procedure;
use App\Models\Setting;
use App\Models\User;
use App\Services\Leaflet\LeafletBuilder;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryPublic;

    private string $originalPublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalPublic = public_path();
        $this->temporaryPublic = storage_path('framework/testing/settings-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->temporaryPublic);
        File::copyDirectory(public_path('brand'), $this->temporaryPublic.'/brand');
        $this->app->usePublicPath($this->temporaryPublic);
    }

    protected function tearDown(): void
    {
        $this->app->usePublicPath($this->originalPublic);
        File::deleteDirectory($this->temporaryPublic);
        parent::tearDown();
    }

    private function data(): array
    {
        return config('clinic');
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function upload(string $content = '<svg xmlns="http://www.w3.org/2000/svg"><circle r="8"/></svg>'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('logo.svg', $content);
    }

    #[DataProvider('methods')]
    public function test_settings_require_verified_admin(string $method): void
    {
        foreach (['et', 'en'] as $locale) {
            $this->call($method, '/'.$locale.'/admin/settings')->assertRedirect('/'.$locale.'/login');
        }
        $this->actingAs(User::factory()->create())->call($method, '/en/admin/settings')->assertForbidden();
        $this->actingAs(User::factory()->unverified()->create(['is_admin' => true]))
            ->call($method, '/en/admin/settings')->assertRedirect('/en/verify-email');
        $this->assertDatabaseCount('settings', 0);
    }

    public static function methods(): array
    {
        return [['GET'], ['PUT']];
    }

    public function test_admin_sees_localized_form_both_translations_and_enabled_navigation(): void
    {
        $this->actingAs($this->admin());
        foreach (['et', 'en'] as $locale) {
            $this->get('/'.$locale.'/admin/settings')->assertOk()
                ->assertSee(__('settings.title', [], $locale))
                ->assertSee('name="clinic[name][et]"', false)->assertSee('name="clinic[name][en]"', false)
                ->assertSee('name="leaflet[disclaimer][en]"', false)
                ->assertSee('role="tab"', false)
                ->assertSee('href="'.route('admin.settings.edit', ['locale' => $locale]).'"', false)
                ->assertSee('brand/logo.svg')->assertSee('112');
        }
    }

    public function test_updates_are_typed_normalized_and_visible_immediately_in_pdf_document(): void
    {
        $settings = app(Settings::class);
        $oldPhone = $settings->get('clinic.phone');
        $this->assertTrue(Cache::has('settings.all'));
        $procedure = Procedure::factory()->withContent()->create();
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'en', null, null);
        $render = fn () => view('pdf.a5.document', ['doc' => $doc, 'settings' => $settings, 'printMode' => false])->render();
        $this->assertStringContainsString($oldPhone, $render());

        $data = $this->data();
        unset($data['brand']['logo_path']);
        $data['clinic']['name'] = ['et' => 'Uus kliinik', 'en' => 'New clinic'];
        $data['clinic']['phone'] = '+372 555 1234';
        $data['brand']['colors']['accent'] = '#aabbcc';
        $data['retention'] = ['saved_leaflet_months' => '24', 'unverified_account_days' => '45'];
        $this->actingAs($this->admin())->put('/en/admin/settings', $data)->assertSessionHasNoErrors()
            ->assertRedirect('/en/admin/settings')->assertSessionHas('success', __('settings.saved'));
        $this->assertFalse(Cache::has('settings.all'));
        foreach ($data['clinic'] as $key => $value) {
            $this->assertSame($value, $settings->get('clinic.'.$key));
        }
        $this->assertSame('#AABBCC', $settings->get('brand.colors')['accent']);
        $this->assertSame(24, $settings->get('retention.saved_leaflet_months'));
        $this->assertSame(45, $settings->get('retention.unverified_account_days'));
        $this->assertSame($data['leaflet']['disclaimer'], $settings->get('leaflet.disclaimer'));
        $html = $render();
        $this->assertStringContainsString('New clinic', $html);
        $this->assertStringContainsString('+372 555 1234', $html);
        $this->assertStringNotContainsString($oldPhone, $html);
    }

    private function validData(): array
    {
        $data = $this->data();
        unset($data['brand']['logo_path']);

        return $data;
    }

    #[DataProvider('invalidValues')]
    public function test_invalid_values_do_not_change_settings(string $key, mixed $value): void
    {
        $data = $this->validData();
        data_set($data, $key, $value);
        $this->actingAs($this->admin())->from('/en/admin/settings')->put('/en/admin/settings', $data)
            ->assertRedirect('/en/admin/settings')->assertSessionHasErrors($key);
        $this->assertDatabaseCount('settings', 0);
    }

    public static function invalidValues(): array
    {
        return [
            ['brand.colors.primary', '#FFF'], ['brand.colors.ink', '#GG0011'],
            ['clinic.phone', '+372-555-1234'], ['clinic.phone', '12345'], ['clinic.after_hours_phone', str_repeat('1', 21)],
            ['clinic.emergency', '11'], ['clinic.emergency', '12345'], ['clinic.emergency', '+112'],
            ['clinic.website', 'http://example.test'], ['clinic.email', 'invalid'],
            ['retention.saved_leaflet_months', 0], ['retention.saved_leaflet_months', 61],
            ['retention.unverified_account_days', 0], ['retention.unverified_account_days', 366],
            ['retention.unverified_account_days', 1.5], ['leaflet.disclaimer.en', null],
            ['leaflet.disclaimer.et', str_repeat('x', 301)],
        ];
    }

    public function test_low_contrast_warns_without_blocking_save(): void
    {
        $data = $this->validData();
        $data['brand']['colors'] = array_fill_keys(array_keys($data['brand']['colors']), '#FFFFFF');
        $this->actingAs($this->admin())->put('/en/admin/settings', $data)->assertSessionHasNoErrors()->assertRedirect('/en/admin/settings');
        $this->assertSame($data['brand']['colors'], app(Settings::class)->get('brand.colors'));
        $this->get('/en/admin/settings')->assertOk()->assertSee(__('settings.contrast_title'))
            ->assertSee(__('settings.contrast.ink', ['ratio' => '1.00', 'minimum' => 7]))
            ->assertSee(__('settings.contrast.primary', ['ratio' => '1.00', 'minimum' => 4.5]))
            ->assertSee(__('settings.contrast.danger', ['ratio' => '1.00', 'minimum' => 4.5]));
    }

    public function test_https_website_and_retention_boundaries_are_accepted(): void
    {
        $this->actingAs($this->admin());
        foreach ([[1, 1], [60, 365]] as [$months, $days]) {
            $data = $this->validData();
            $data['clinic']['website'] = 'https://example.test/clinic';
            $data['retention'] = ['saved_leaflet_months' => $months, 'unverified_account_days' => $days];
            $this->put('/en/admin/settings', $data)->assertSessionHasNoErrors();
        }
        $this->assertSame('https://example.test/clinic', app(Settings::class)->get('clinic.website'));
    }

    public function test_missing_english_disclaimer_is_rejected_and_old_input_is_kept(): void
    {
        $data = $this->validData();
        unset($data['leaflet']['disclaimer']['en']);
        $data['clinic']['name']['et'] = 'Muudetud nimi';
        $this->actingAs($this->admin())->from('/et/admin/settings')->put('/et/admin/settings', $data)
            ->assertRedirect('/et/admin/settings')->assertSessionHasErrors('leaflet.disclaimer.en')
            ->assertSessionHasInput('clinic.name.et', 'Muudetud nimi');
        $this->withCookie(config('session.cookie'), session()->getId())->get('/et/admin/settings')
            ->assertOk()->assertSee('Muudetud nimi')->assertSee(__('settings.fields.disclaimer'));
    }

    public function test_logo_is_sanitized_original_is_backed_up_and_only_last_five_backups_remain(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(10, 0));
        $original = File::get(public_path('brand/logo.svg'));
        $this->actingAs($this->admin());
        for ($i = 0; $i < 7; $i++) {
            $data = $this->validData();
            $data['logo'] = $this->upload('<svg xmlns="http://www.w3.org/2000/svg" onload="bad()"><script>bad()</script><circle r="'.($i + 1).'"/><image href="https://example.test/tracker"/></svg>');
            $this->put('/en/admin/settings', $data)->assertSessionHasNoErrors();
            if ($i === 0) {
                $this->assertSame($original, File::get(public_path('brand/backup/logo-20261124-100000.svg')));
            }
            $this->travel(1)->seconds();
        }
        $svg = File::get(public_path('brand/logo.svg'));
        $this->assertStringContainsString('<circle r="7"', $svg);
        foreach (['script', 'onload', 'image', 'https:'] as $removed) {
            $this->assertStringNotContainsString($removed, $svg);
        }
        $this->assertSame('brand/logo.svg', app(Settings::class)->get('brand.logo_path'));
        $backups = File::glob(public_path('brand/backup/*.svg'));
        $this->assertCount(5, $backups);
        $this->assertFileDoesNotExist(public_path('brand/backup/logo-20261124-100001.svg'));
        $this->assertFileExists(public_path('brand/backup/logo-20261124-100006.svg'));
    }

    public function test_repeated_uploads_in_same_second_keep_distinct_backups(): void
    {
        $this->freezeTime();
        $this->actingAs($this->admin());
        for ($i = 0; $i < 3; $i++) {
            $this->put('/en/admin/settings', [...$this->validData(), 'logo' => $this->upload()])->assertSessionHasNoErrors();
        }
        $this->assertCount(3, File::glob(public_path('brand/backup/*.svg')));
    }

    public function test_invalid_logo_leaves_existing_logo_and_settings_untouched(): void
    {
        $original = File::get(public_path('brand/logo.svg'));
        $this->actingAs($this->admin());
        foreach ([$this->upload('<!DOCTYPE svg><svg/>'), $this->upload('invalid'), UploadedFile::fake()->create('large.svg', 201), UploadedFile::fake()->createWithContent('logo.png', '<svg/>')] as $upload) {
            $this->put('/en/admin/settings', [...$this->validData(), 'logo' => $upload])->assertSessionHasErrors('logo');
        }
        $this->assertSame($original, File::get(public_path('brand/logo.svg')));
        $this->assertDatabaseCount('settings', 0);
        $this->assertSame([], File::glob(public_path('brand/backup/*.svg')));
    }

    public function test_valid_logo_larger_than_icon_limit_is_accepted(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><circle r="8"/>'.str_repeat(' ', 120 * 1024).'</svg>';

        $this->actingAs($this->admin())->put('/en/admin/settings', [...$this->validData(), 'logo' => $this->upload($svg)])
            ->assertSessionHasNoErrors()->assertRedirect('/en/admin/settings');

        $this->assertSame($svg, File::get(public_path('brand/logo.svg')));
        $this->assertSame('brand/logo.svg', app(Settings::class)->get('brand.logo_path'));
    }

    public function test_logo_over_200_kb_is_rejected_on_logo_field(): void
    {
        $original = File::get(public_path('brand/logo.svg'));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg">'.str_repeat(' ', 200 * 1024).'</svg>';

        $this->actingAs($this->admin())->from('/en/admin/settings')
            ->put('/en/admin/settings', [...$this->validData(), 'logo' => $this->upload($svg)])
            ->assertRedirect('/en/admin/settings')->assertSessionHasErrors('logo');

        $this->assertSame($original, File::get(public_path('brand/logo.svg')));
        $this->assertDatabaseCount('settings', 0);
        $this->assertSame([], File::glob(public_path('brand/backup/*.svg')));
    }

    public function test_database_failure_rolls_back_all_values_and_leaves_logo_untouched(): void
    {
        $setting = Setting::factory()->create(['key' => 'clinic.phone', 'value' => '+372 500 0000']);
        app(Settings::class)->get('clinic.phone');
        $original = File::get(public_path('brand/logo.svg'));
        DB::listen(function ($query): void {
            if (str_contains($query->sql, 'insert into "settings"') && in_array('leaflet.disclaimer', $query->bindings, true)) {
                throw new RuntimeException('Simulated settings write failure');
            }
        });
        $this->actingAs($this->admin())->put('/en/admin/settings', [...$this->validData(), 'logo' => $this->upload()])->assertServerError();
        $this->assertDatabaseCount('settings', 1);
        $this->assertSame($setting->value, app(Settings::class)->get('clinic.phone'));
        $this->assertSame($original, File::get(public_path('brand/logo.svg')));
    }

    public function test_logo_write_failure_rolls_back_settings_and_does_not_keep_a_failed_backup(): void
    {
        $original = File::get(public_path('brand/logo.svg'));
        File::partialMock()->shouldReceive('replace')->once()->with(public_path('brand/logo.svg'), \Mockery::type('string'))
            ->andThrow(new \ErrorException('Simulated logo write failure'));
        $this->actingAs($this->admin())->put('/en/admin/settings', [...$this->validData(), 'logo' => $this->upload()])
            ->assertSessionHasErrors('logo');
        $this->assertDatabaseCount('settings', 0);
        $this->assertSame($original, File::get(public_path('brand/logo.svg')));
        $this->assertSame([], File::glob(public_path('brand/backup/*.svg')));
        $this->assertFalse(Cache::has('settings.all'));
    }

    public function test_settings_translations_have_matching_keys(): void
    {
        $this->assertSame(array_keys(Arr::dot(require lang_path('et/settings.php'))), array_keys(Arr::dot(require lang_path('en/settings.php'))));
    }
}
