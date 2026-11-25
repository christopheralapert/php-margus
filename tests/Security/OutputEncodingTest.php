<?php

namespace Tests\Security;

use App\Models\Category;
use App\Models\Procedure;
use App\Models\User;
use App\Services\Leaflet\LeafletBuilder;
use App\Support\Settings;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;

final class OutputEncodingTest extends SecurityTestCase
{
    private const HTML = '<img src=x onerror="securityMarker()">';

    public function test_stored_text_and_patient_name_are_escaped_in_patient_and_admin_views(): void
    {
        $category = Category::factory()->create(['name' => ['et' => self::HTML, 'en' => self::HTML]]);
        $procedure = Procedure::factory()->for($category)->withContent()->create([
            'name' => ['et' => self::HTML, 'en' => self::HTML], 'summary' => ['et' => self::HTML, 'en' => self::HTML],
        ]);
        $procedure->adviceItems()->first()->update(['text' => ['et' => self::HTML, 'en' => self::HTML]]);
        app(Settings::class)->set('clinic.name', ['et' => self::HTML, 'en' => self::HTML]);
        foreach (['en', 'et'] as $locale) {
            $this->withSession(['wizard' => ['procedures' => [$procedure->id], 'first_name' => self::HTML, 'consent' => true]])
                ->get('/'.$locale.'/leaflet/preview')->assertOk()->assertDontSee(self::HTML, false)->assertSee(e(self::HTML), false);
            $this->get('/'.$locale.'/leaflet')->assertOk()->assertDontSee(self::HTML, false)->assertSee(e(self::HTML), false);
            $this->get('/'.$locale.'/l/'.$procedure->slug)->assertOk()->assertDontSee(self::HTML, false)->assertSee(e(self::HTML), false);
        }
        $this->actingAs(User::factory()->create(['is_admin' => true, 'name' => self::HTML]));
        foreach (['/admin/categories', '/admin/categories/'.$category->id.'/edit', '/admin/procedures', '/admin/procedures/'.$procedure->id.'/edit', '/admin/settings', '/profile'] as $path) {
            $this->get('/en'.$path)->assertOk()->assertDontSee(self::HTML, false)->assertSee(e(self::HTML), false);
        }
    }

    public function test_patient_and_database_text_remain_escaped_in_pdf_html_including_generated_cards(): void
    {
        $procedure = Procedure::factory()->withContent()->create(['name' => ['en' => self::HTML], 'summary' => ['en' => self::HTML]]);
        foreach ($procedure->adviceItems as $item) {
            $item->update(['text' => ['en' => self::HTML]]);
        }
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'en', self::HTML, null);
        $html = view('pdf.a5.document', ['doc' => $doc, 'settings' => app(Settings::class), 'printMode' => false, 'categoryProof' => false, 'notesPages' => 0])->render();
        $this->assertStringNotContainsString(self::HTML, $html);
        $this->assertStringContainsString(e(self::HTML), $html);
    }

    public static function errors(): array
    {
        return [[403], [404], [419], [429], [500], [503]];
    }

    #[DataProvider('errors')]
    public function test_error_pages_escape_clinic_text_and_do_not_render_exception_input(int $status): void
    {
        config(['clinic.clinic.name.en' => self::HTML, 'clinic.clinic.phone' => self::HTML]);
        app()->setLocale('en');
        $html = view('errors.'.$status, ['exception' => new \RuntimeException(self::HTML)])->render();
        $this->assertStringNotContainsString(self::HTML, $html);
        $this->assertStringContainsString(e(self::HTML), $html);
    }

    public function test_chat_renders_untrusted_content_as_text_and_never_as_html(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $this->llm->response = self::HTML;
        $this->postJson('/en/chat', ['procedures' => [$procedure->slug], 'message' => self::HTML])->assertOk()->assertJsonPath('reply', self::HTML);
        $this->get('/en/l/'.$procedure->slug)->assertOk()->assertSee('x-text="item.content"', false)->assertDontSee('x-html', false)->assertDontSee(self::HTML, false);
    }

    public function test_raw_blade_outputs_remain_limited_to_reviewed_safe_producers(): void
    {
        // Deliberate inventory: any new raw output must be reviewed and added here.
        $expected = [
            'admin/categories/_form.blade.php' => 1, 'admin/categories/index.blade.php' => 1,
            'components/category-icon.blade.php' => 1,
            'pdf/a5/back.blade.php' => 3, 'pdf/a5/card-row.blade.php' => 1, 'pdf/a5/card.blade.php' => 1,
            'pdf/a5/cards.blade.php' => 1, 'pdf/a5/cover.blade.php' => 1, 'pdf/a5/followup.blade.php' => 1,
            'pdf/a5/general-topic-row.blade.php' => 1, 'pdf/a5/general.blade.php' => 1, 'pdf/a5/icons.blade.php' => 3,
            'pdf/a5/redflag.blade.php' => 5, 'pdf/a5/section-band.blade.php' => 1, 'pdf/a5/topics.blade.php' => 3,
            'pdf/a5/timeline-step.blade.php' => 1, 'pdf/a5/timeline.blade.php' => 1, 'pdf/trifold/panel.blade.php' => 12,
        ];
        $actual = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            $count = substr_count($file->getContents(), '{!!');
            if ($count) {
                $actual[str_replace('\\', '/', $file->getRelativePathname())] = $count;
            }
        }
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }
}
