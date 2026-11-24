<?php

namespace Tests\Feature\Admin;

use App\Enums\ProcedureStatus;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryPublic;

    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h4"/></svg>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryPublic = storage_path('framework/testing/admin-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->temporaryPublic.'/icons/categories');
        $this->app->usePublicPath($this->temporaryPublic);
    }

    protected function tearDown(): void
    {
        // Clean up with a real filesystem: the File facade may be a partial mock in a test,
        // and on Linux its delete() expectation would also match the cleanup calls.
        (new Filesystem)->deleteDirectory($this->temporaryPublic);
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function category(array $attributes = []): Category
    {
        $category = Category::factory()->create(array_merge(['slug' => 'test-category', 'icon_path' => 'icons/categories/test-category.svg'], $attributes));
        File::put(public_path($category->icon_path), self::SVG);

        return $category;
    }

    private function data(array $overrides = []): array
    {
        $category = Category::factory()->make();

        return array_replace([
            'name' => $category->getTranslations('name'),
            'slug' => 'new-category',
            'color' => '#aabbcc',
            'sort' => 3,
            'is_active' => '1',
        ], $overrides);
    }

    private function icon(string $svg = self::SVG): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('icon.svg', $svg);
    }

    #[DataProvider('adminEndpoints')]
    public function test_admin_routes_require_authentication_and_authorization(string $method, string $path): void
    {
        $category = $this->category();
        $path = str_replace('{id}', (string) $category->id, $path);

        foreach (['et', 'en'] as $locale) {
            $this->call($method, '/'.$locale.$path)->assertRedirect('/'.$locale.'/login');
        }

        $this->actingAs(User::factory()->create());
        $this->call($method, '/en'.$path)->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public static function adminEndpoints(): array
    {
        return [
            ['GET', '/admin'], ['GET', '/admin/categories'], ['GET', '/admin/categories/create'],
            ['POST', '/admin/categories'], ['GET', '/admin/categories/{id}/edit'],
            ['PUT', '/admin/categories/{id}'], ['DELETE', '/admin/categories/{id}'],
        ];
    }

    public function test_unverified_admin_is_redirected_to_verification(): void
    {
        $this->actingAs(User::factory()->unverified()->create(['is_admin' => true]))
            ->get('/et/admin')->assertRedirect('/et/verify-email');
    }

    public function test_admin_can_view_pages_in_both_languages_and_sorted_category_counts(): void
    {
        $category = $this->category(['sort' => 9, 'name' => ['et' => 'Viimane', 'en' => 'Last']]);
        Category::factory()->create(['sort' => 0, 'name' => ['et' => 'Esimene', 'en' => 'First']]);
        foreach (ProcedureStatus::cases() as $status) {
            Procedure::factory()->for($category)->create(['status' => $status]);
        }
        $this->actingAs($this->admin());

        foreach (['et', 'en'] as $locale) {
            $this->get('/'.$locale.'/admin')->assertOk()->assertViewHas('categoryCount', 2)
                ->assertViewHas('procedureCounts', fn ($counts) => $counts->all() === ['draft' => 1, 'published' => 1, 'reviewed' => 1]);
            $this->get('/'.$locale.'/admin/categories')->assertOk()->assertSeeInOrder(['Esimene', 'Viimane'])
                ->assertViewHas('categories', fn ($categories) => $categories->last()->procedures_count === 3);
            $this->get('/'.$locale.'/admin/categories/create')->assertOk();
            $this->get('/'.$locale.'/admin/categories/'.$category->id.'/edit')->assertOk()->assertSee('Viimane');
        }
    }

    public function test_admin_can_create_sanitized_icon_and_update_rename_and_delete_category(): void
    {
        $this->actingAs($this->admin())->post('/en/admin/categories', $this->data([
            'icon' => $this->icon(str_replace('<path', '<script>alert(1)</script><path onload="bad()"', self::SVG)),
            'icon_path' => '../outside.svg',
        ]))->assertSessionHasNoErrors()->assertRedirect('/en/admin/categories');
        $category = Category::sole();
        $this->assertSame('#AABBCC', $category->color);
        $this->assertSame('icons/categories/new-category.svg', $category->icon_path);
        $this->assertStringNotContainsString('script', File::get(public_path($category->icon_path)));
        $this->assertStringNotContainsString('onload', File::get(public_path($category->icon_path)));

        $this->put('/en/admin/categories/'.$category->id, $this->data(['slug' => 'renamed', 'is_active' => '0']))
            ->assertSessionHasNoErrors()->assertRedirect('/en/admin/categories');
        $this->assertFileDoesNotExist(public_path('icons/categories/new-category.svg'));
        $this->assertFileExists(public_path('icons/categories/renamed.svg'));
        $this->assertFalse($category->fresh()->is_active);

        $this->put('/en/admin/categories/'.$category->id, $this->data(['slug' => 'renamed', 'icon' => $this->icon('<svg><circle r="4"/></svg>')]))
            ->assertSessionHasNoErrors();
        $this->assertStringContainsString('<circle', File::get(public_path('icons/categories/renamed.svg')));

        $this->delete('/en/admin/categories/'.$category->id)->assertRedirect('/en/admin/categories')->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertFileDoesNotExist(public_path('icons/categories/renamed.svg'));
    }

    public function test_delete_is_refused_if_category_has_procedures(): void
    {
        $category = $this->category();
        Procedure::factory()->for($category)->create();
        $this->actingAs($this->admin())->delete('/et/admin/categories/'.$category->id)
            ->assertRedirect('/et/admin/categories')->assertSessionHas('error', __('admin.has_procedures'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertFileExists(public_path($category->icon_path));
    }

    public function test_validation_keeps_old_input_and_translates_errors(): void
    {
        $this->category(['slug' => 'duplicate']);
        $this->actingAs($this->admin())->from('/et/admin/categories/create')->post('/et/admin/categories', $this->data([
            'slug' => 'duplicate', 'color' => 'red', 'name' => ['et' => 'Nimi'], 'sort' => -1,
        ]))->assertRedirect('/et/admin/categories/create')
            ->assertSessionHasErrors(['slug', 'color', 'name.en', 'sort', 'icon'])
            ->assertSessionHasInput('name.et', 'Nimi');
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/et/admin/categories/create')->assertSee('Nimi')
            ->assertSee(__('validation.required', ['attribute' => __('admin.fields.name_en')]));
    }

    public function test_invalid_upload_never_overwrites_original_or_renames_icon(): void
    {
        $category = $this->category();
        $this->actingAs($this->admin())->put('/en/admin/categories/'.$category->id, $this->data([
            'slug' => 'renamed', 'icon' => $this->icon('<!DOCTYPE svg><svg/>'),
        ]))->assertSessionHasErrors('icon');
        $this->assertSame(self::SVG, File::get(public_path($category->icon_path)));
        $this->assertSame('test-category', $category->fresh()->slug);
        $this->assertFileDoesNotExist(public_path('icons/categories/renamed.svg'));
    }

    #[DataProvider('invalidSlugs')]
    public function test_rejects_unsafe_slugs(string $slug): void
    {
        $this->actingAs($this->admin())->post('/en/admin/categories', $this->data(['slug' => $slug, 'icon' => $this->icon()]))
            ->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('categories', 0);
        $this->assertCount(0, File::files(public_path('icons/categories')));
    }

    public static function invalidSlugs(): array
    {
        return [['../outside'], ['UPPER'], ['a/b'], ['a.b'], ['a_b'], ['-start'], ['end-'], ['two--hyphens']];
    }

    public function test_rejects_oversized_and_non_svg_uploads(): void
    {
        $this->actingAs($this->admin());
        foreach ([UploadedFile::fake()->create('large.svg', 51, 'image/svg+xml'), UploadedFile::fake()->createWithContent('icon.png', self::SVG), $this->icon('not XML')] as $icon) {
            $this->post('/en/admin/categories', $this->data(['icon' => $icon]))->assertSessionHasErrors('icon');
        }
        $this->assertCount(0, File::files(public_path('icons/categories')));
    }

    public function test_existing_destination_icon_is_not_overwritten(): void
    {
        File::put(public_path('icons/categories/new-category.svg'), self::SVG);
        $this->actingAs($this->admin())->post('/en/admin/categories', $this->data(['icon' => $this->icon()]))
            ->assertSessionHasErrors('slug');
        $this->assertSame(self::SVG, File::get(public_path('icons/categories/new-category.svg')));
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_unchecked_active_checkbox_is_saved_as_false(): void
    {
        $data = $this->data(['icon' => $this->icon()]);
        unset($data['is_active']);
        $this->actingAs($this->admin())->post('/en/admin/categories', $data)->assertSessionHasNoErrors();
        $this->assertFalse(Category::sole()->is_active);
    }

    public function test_failed_rename_restores_database_and_files(): void
    {
        $category = $this->category();
        $source = public_path($category->icon_path);
        File::partialMock()->shouldReceive('delete')->once()->with($source)->andReturn(false);

        $this->actingAs($this->admin())->put('/en/admin/categories/'.$category->id, $this->data(['slug' => 'renamed']))
            ->assertSessionHasErrors('icon');

        $this->assertSame('test-category', $category->fresh()->slug);
        $this->assertSame(self::SVG, File::get($source));
        $this->assertFileDoesNotExist(public_path('icons/categories/renamed.svg'));
    }

    public function test_database_failure_does_not_replace_icon(): void
    {
        $category = $this->category();
        Category::saving(function (): void {
            throw new RuntimeException('Simulated database failure');
        });

        $this->actingAs($this->admin())->put('/en/admin/categories/'.$category->id, $this->data([
            'slug' => 'renamed', 'icon' => $this->icon('<svg><circle r="2"/></svg>'),
        ]))->assertServerError();

        $this->assertSame('test-category', $category->fresh()->slug);
        $this->assertSame(self::SVG, File::get(public_path($category->icon_path)));
        $this->assertFileDoesNotExist(public_path('icons/categories/renamed.svg'));
    }

    public function test_rendering_rechecks_stored_icons_before_inlining(): void
    {
        $category = $this->category();
        File::put(public_path($category->icon_path), '<svg><script>unsafeStoredIcon()</script><circle r="2"/></svg>');

        $this->actingAs($this->admin())->get('/en/admin/categories')->assertOk()
            ->assertDontSee('unsafeStoredIcon')->assertSee('<circle r="2"', false);
    }
}
