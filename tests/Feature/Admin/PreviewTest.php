<?php

namespace Tests\Feature\Admin;

use App\Models\Procedure;
use App\Models\User;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\LeafletDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    use RefreshDatabase;

    public static function previews(): array
    {
        return [
            'draft et' => ['et', true],
            'draft en' => ['en', true],
            'inactive et' => ['et', false],
            'inactive en' => ['en', false],
        ];
    }

    #[DataProvider('previews')]
    public function test_admin_can_preview_draft_and_inactive_procedures(string $locale, bool $active): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $procedure = Procedure::factory()->draft()->withContent()->create(['is_active' => $active]);
        Procedure::factory()->create();
        $document = null;
        View::composer('pdf.a5.document', function (ViewInstance $view) use (&$document): void {
            $document = $view->getData()['doc'];
        });

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/et/admin/procedures/'.$procedure->id.'/preview.pdf?lang='.$locale)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="preview-'.$procedure->slug.'-'.$locale.'.pdf"');

        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('/Lang ('.$locale.')', $pdf);
        $this->assertMatchesRegularExpression('/\/ca 0\.06\b/', $pdf);
        $this->assertInstanceOf(LeafletDocument::class, $document);
        $this->assertSame([$procedure->id], array_map(fn (array $section) => $section['procedure']->id, $document->sections));
        $this->assertSame($locale === 'et' ? 'Mari' : 'Mary', $document->firstName);
        $this->assertSame('2026-11-24', $document->date->toDateString());
    }

    public function test_print_mode_renders_a_print_sheet(): void
    {
        $procedure = Procedure::factory()->withContent()->create(['is_active' => false]);
        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/en/admin/procedures/'.$procedure->id.'/preview.pdf?print=1')->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[0 0 476\.22\d* 651\.96\d*\]/', $response->getContent());
    }

    public static function languageFallbacks(): array
    {
        return [['et', ''], ['en', ''], ['et', '?lang=de'], ['en', '?lang=de'], ['en', '?lang[]=et']];
    }

    #[DataProvider('languageFallbacks')]
    public function test_missing_or_invalid_language_uses_the_current_locale(string $locale, string $query): void
    {
        $procedure = Procedure::factory()->create();
        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/'.$locale.'/admin/procedures/'.$procedure->id.'/preview.pdf'.$query)->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="preview-'.$procedure->slug.'-'.$locale.'.pdf"');

        $this->assertStringContainsString('/Lang ('.$locale.')', $response->getContent());
    }

    public function test_preview_requires_admin_access_and_an_existing_procedure(): void
    {
        $procedure = Procedure::factory()->create();
        $url = '/et/admin/procedures/'.$procedure->id.'/preview.pdf';
        $this->get($url)->assertRedirect('/et/login');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/et/admin/procedures/999999/preview.pdf')->assertNotFound();
    }

    public function test_edit_and_index_offer_preview_links_but_create_does_not(): void
    {
        $procedure = Procedure::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $response = $this->get('/en/admin/procedures/'.$procedure->id.'/edit')->assertOk();
        foreach (['et', 'en'] as $locale) {
            foreach ([[], ['print' => 1]] as $options) {
                $url = route('admin.procedures.preview', ['locale' => 'en', 'procedure' => $procedure, 'lang' => $locale, ...$options]);
                $response->assertSee('href="'.e($url).'" target="_blank" rel="noopener"', false);
            }
        }
        $url = route('admin.procedures.preview', ['locale' => 'en', 'procedure' => $procedure]);
        $this->get('/en/admin/procedures')->assertOk()->assertSee('href="'.$url.'" target="_blank" rel="noopener"', false);
        $this->get('/en/admin/procedures/create')->assertOk()->assertDontSee('preview.pdf');
    }

    public function test_patient_wizard_and_default_builder_still_exclude_inactive_procedures(): void
    {
        $active = Procedure::factory()->create();
        $inactive = Procedure::factory()->create(['is_active' => false, 'name' => ['et' => 'Peidetud protseduur', 'en' => 'Hidden procedure']]);

        $this->get('/en/leaflet')->assertOk()->assertSee($active->getTranslation('name', 'en'))
            ->assertDontSee($inactive->getTranslation('name', 'en'));
        $this->post('/en/leaflet', ['procedures' => [$inactive->id]])->assertSessionHasErrors('procedures.0');
        $doc = app(LeafletBuilder::class)->build([$active->id, $inactive->id], 'en', null, null);
        $this->assertSame([$active->id], array_map(fn (array $section) => $section['procedure']->id, $doc->sections));
        $this->withSession(['wizard.procedures' => [$inactive->id]])
            ->get('/en/leaflet/preview')->assertRedirect('/en/leaflet');
    }
}
