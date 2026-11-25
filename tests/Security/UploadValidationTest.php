<?php

namespace Tests\Security;

use App\Enums\TimelinePhase;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\TimelineStep;
use App\Models\User;
use App\Services\Admin\ClinicSettings;
use App\Services\Admin\SvgSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;

final class UploadValidationTest extends SecurityTestCase
{
    private string $temporaryPublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryPublic = storage_path('framework/testing/security-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->temporaryPublic);
        $this->app->usePublicPath($this->temporaryPublic);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryPublic);
        parent::tearDown();
    }

    private function categoryData(): array
    {
        $category = Category::factory()->make();

        return ['name' => $category->getTranslations('name'), 'slug' => 'safe-category', 'color' => '#123456', 'sort' => 0, 'is_active' => true];
    }

    private function procedureData(): array
    {
        $category = Category::where('slug', 'safe-category')->first() ?? Category::factory()->create(['slug' => 'safe-category']);
        $procedure = Procedure::factory()->make(['category_id' => $category->id]);
        $data = $procedure->only(['category_id', 'slug', 'sort', 'is_active', 'color']);
        $data['slug'] = 'safe-procedure';
        $data['status'] = 'draft';
        foreach (['name', 'summary', 'search_keywords', 'follow_up', 'review_note'] as $field) {
            $data[$field] = array_replace(['et' => '', 'en' => ''], $procedure->getTranslations($field));
        }
        foreach (TimelinePhase::cases() as $phase) {
            $step = TimelineStep::factory()->make(['procedure_id' => 0, 'phase' => $phase]);
            foreach (['marker', 'title', 'body'] as $field) {
                $data['timeline_steps'][$phase->value][$field] = $step->getTranslations($field);
            }
        }
        $data['advice_items'] = [];

        return $data;
    }

    public static function activeSvg(): array
    {
        return array_map(fn ($body) => [$body], [
            '<script>alert(1)</script>',
            '<foreignObject><div xmlns="http://www.w3.org/1999/xhtml">bad</div></foreignObject>',
            '<use href="https://example.invalid/asset.svg#x"/><image href="javascript:alert(1)"/>',
            '<use href="javascript:alert(1)"/><image href="https://example.invalid/x.png"/>',
            '<path onload="bad()" OnClIcK="bad()" ONMOUSEOVER="bad()" d="M0 0"/>',
            '<set attributeName="href" to="https://example.invalid/x"/><animate attributeName="href" values="javascript:bad()"/>',
            '<path style="fill:url(https://example.invalid/x);width:expression(bad())"/>',
            '<title><![CDATA[</title><img src=x onerror=bad()>]]></title>',
        ]);
    }

    #[DataProvider('activeSvg')]
    public function test_active_svg_is_neutralized_in_all_three_upload_flows(string $body): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 120">'.$body.'<circle r="3"/></svg>';
        $upload = fn () => UploadedFile::fake()->createWithContent('untrusted-name.svg', $svg);
        $this->post('/en/admin/categories', $this->categoryData() + ['icon' => $upload(), 'icon_path' => '../escape.svg'])
            ->assertSessionHasNoErrors();
        $this->post('/en/admin/procedures', $this->procedureData() + ['illustration' => $upload(), 'illustration_path' => '../escape.svg'])
            ->assertSessionHasNoErrors();
        $this->put('/en/admin/settings', app(ClinicSettings::class)->values() + ['logo' => $upload()])->assertSessionHasNoErrors();
        $expected = ['brand/logo.svg', 'icons/categories/safe-category.svg', 'illustrations/safe-category/safe-procedure.svg'];
        $actual = collect(File::allFiles($this->temporaryPublic))->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))->sort()->values()->all();
        sort($expected);
        $this->assertSame($expected, $actual);
        foreach ($expected as $path) {
            $clean = File::get(public_path($path));
            $document = new \DOMDocument;
            $this->assertTrue($document->loadXML($clean, LIBXML_NONET));
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//*[local-name()="script" or local-name()="foreignObject" or local-name()="use" or local-name()="image" or local-name()="animate" or local-name()="set"]')->length);
            foreach ($xpath->query('//@*') as $attribute) {
                $this->assertDoesNotMatchRegularExpression('/^(?:on|style)/i', $attribute->nodeName);
                $this->assertDoesNotMatchRegularExpression('/javascript:|https:|expression\(/i', $attribute->nodeValue);
            }
        }
    }

    public static function invalidFiles(): array
    {
        return [
            ['bad.svg', '<!DOCTYPE svg [<!ENTITY secret SYSTEM "file:///nonexistent">]><svg>&secret;</svg>'],
            ['bad.svg', '<!DOCTYPE svg><svg/>'], ['bad.svg', '<?php echo "bad"; ?>'],
            ['bad.svg', '<html>not svg</html>'], ['bad.png', 'not an image'],
        ];
    }

    #[DataProvider('invalidFiles')]
    public function test_invalid_or_disguised_uploads_never_write_files(string $filename, string $content): void
    {
        $upload = fn () => UploadedFile::fake()->createWithContent($filename, $content);
        $this->post('/en/admin/categories', $this->categoryData() + ['icon' => $upload()])->assertSessionHasErrors('icon');
        $this->post('/en/admin/procedures', $this->procedureData() + ['illustration' => $upload()])->assertSessionHasErrors('illustration');
        $this->put('/en/admin/settings', app(ClinicSettings::class)->values() + ['logo' => $upload()])->assertSessionHasErrors('logo');
        $this->assertCount(0, File::allFiles($this->temporaryPublic));
        $this->assertDatabaseCount('procedures', 0);
    }

    public function test_oversized_svg_and_png_and_traversal_slugs_are_rejected_before_writes(): void
    {
        $category = $this->categoryData();
        $procedure = $this->procedureData();
        $settings = app(ClinicSettings::class)->values();
        $this->post('/en/admin/categories', $category + ['icon' => UploadedFile::fake()->create('large.svg', 51)])->assertSessionHasErrors('icon');
        foreach ([['large.svg', 201], ['large.png', 2049]] as [$name, $size]) {
            $this->post('/en/admin/procedures', $procedure + ['illustration' => UploadedFile::fake()->create($name, $size)])->assertSessionHasErrors('illustration');
        }
        $this->put('/en/admin/settings', $settings + ['logo' => UploadedFile::fake()->create('large.svg', 201)])->assertSessionHasErrors('logo');
        foreach (['../escape', 'a/b', "safe\r\nX-Test: yes", "x' OR 1=1 --"] as $slug) {
            $upload = UploadedFile::fake()->createWithContent('safe.svg', '<svg/>');
            $this->post('/en/admin/categories', array_replace($category, ['slug' => $slug, 'icon' => $upload]))->assertSessionHasErrors('slug');
            $this->post('/en/admin/procedures', array_replace($procedure, ['slug' => $slug, 'illustration' => $upload]))->assertSessionHasErrors('slug');
        }
        $this->assertCount(0, File::allFiles($this->temporaryPublic));
    }

    public function test_sanitizer_rejects_oversize_before_xml_parsing(): void
    {
        $this->expectException(ValidationException::class);
        app(SvgSanitizer::class)->sanitize(str_repeat('x', 51 * 1024));
    }

    public function test_stored_icons_are_sanitized_at_each_raw_web_output(): void
    {
        $category = Category::factory()->create(['slug' => 'safe-category', 'icon_path' => 'icons/categories/safe-category.svg']);
        Procedure::factory()->for($category)->create();
        File::ensureDirectoryExists(public_path('icons/categories'));
        File::put(public_path($category->icon_path), '<svg><script>storedMarker()</script><path onload="storedMarker()" d="M0 0"/></svg>');
        foreach (['/en/leaflet', '/en/admin/categories', '/en/admin/categories/'.$category->id.'/edit'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('storedMarker', false)->assertSee('<path', false);
        }
    }
}
