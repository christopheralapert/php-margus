<?php

namespace Tests\Feature\Admin;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use App\Models\AdviceItem;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\TimelineStep;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProcedureTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryPublic;

    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 120"><rect width="160" height="120" fill="#aabbcc"/></svg>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryPublic = storage_path('framework/testing/procedures-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->temporaryPublic);
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

    private function data(?Procedure $procedure = null): array
    {
        $procedure ??= Procedure::factory()->make(['category_id' => Category::factory()->create()->id]);
        $data = $procedure->only(['category_id', 'slug', 'sort', 'is_active', 'color', 'reviewed_by']);
        foreach (['name', 'summary', 'search_keywords', 'follow_up', 'review_note'] as $field) {
            $data[$field] = array_replace(['et' => '', 'en' => ''], $procedure->getTranslations($field));
        }
        $data['status'] = $procedure->status->value;
        foreach (TimelinePhase::cases() as $phase) {
            $step = $procedure->exists ? $procedure->timelineSteps->firstWhere('phase', $phase) : null;
            $step ??= TimelineStep::factory()->make(['phase' => $phase, 'procedure_id' => 0]);
            foreach (['marker', 'title', 'body'] as $field) {
                $data['timeline_steps'][$phase->value][$field] = $step->getTranslations($field);
            }
        }
        $data['advice_items'] = [];
        foreach (AdviceSection::cases() as $section) {
            $items = $procedure->exists ? $procedure->adviceItems->where('section', $section) : collect([AdviceItem::factory()->make(['section' => $section, 'procedure_id' => 0])]);
            $data['advice_items'][$section->value] = $items->map(fn ($item) => ['text' => $item->getTranslations('text'), 'merge_key' => $item->merge_key])->values()->all();
        }

        return $data;
    }

    private function createProcedure(): Procedure
    {
        $this->actingAs($this->admin())->post('/en/admin/procedures', $this->data())->assertSessionHasNoErrors();

        return Procedure::sole();
    }

    private function svg(string $content = self::SVG): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('art.svg', $content);
    }

    private function png(int $width): UploadedFile
    {
        $image = imagecreatetruecolor($width, 120);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('art.png', $bytes);
    }

    public static function endpoints(): array
    {
        return [['GET', ''], ['GET', '/create'], ['POST', ''], ['GET', '/1/edit'], ['PUT', '/1'], ['DELETE', '/1']];
    }

    #[DataProvider('endpoints')]
    public function test_routes_require_admin(string $method, string $suffix): void
    {
        Procedure::factory()->create(['id' => 1]);
        $this->call($method, '/en/admin/procedures'.$suffix)->assertRedirect('/en/login');
        $this->actingAs(User::factory()->create())->call($method, '/en/admin/procedures'.$suffix)->assertForbidden();
    }

    public function test_admin_can_create_and_render_full_bilingual_content(): void
    {
        $data = $this->data();
        $data['advice_items']['do'][0]['merge_key'] = 'general.keep_clean';
        $data['advice_items']['pain'][] = ['text' => ['et' => '', 'en' => ''], 'merge_key' => ''];
        $this->actingAs($this->admin())->get('/en/admin/procedures/create')->assertOk()->assertSee(__('admin.procedure.new'));
        $this->post('/en/admin/procedures', $data)->assertRedirect('/en/admin/procedures')->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $this->assertSame($data['name'], $procedure->getTranslations('name'));
        $this->assertSame('', $procedure->illustration_path);
        $this->assertDatabaseCount('timeline_steps', 4);
        $this->assertDatabaseCount('advice_items', 8);
        foreach ($procedure->timelineSteps as $step) {
            $this->assertSame($data['timeline_steps'][$step->phase->value]['body'], $step->getTranslations('body'));
        }
        $this->get('/en/admin/procedures/'.$procedure->id.'/edit')->assertOk()
            ->assertSee('general.keep_clean')->assertSee('name="advice_items[do][2][text][en]"', false);
        $this->get('/en/admin/procedures')->assertOk()->assertSee($data['name']['en']);
        $this->get('/et/admin/procedures/'.$procedure->id.'/edit')->assertOk()->assertSee(__('admin.procedure.save', [], 'et'));
    }

    public function test_index_filters_current_locale_and_sorts_by_category_then_sort(): void
    {
        $a = Category::factory()->create(['sort' => 0]);
        $b = Category::factory()->create(['sort' => 1]);
        $first = Procedure::factory()->for($a)->create(['sort' => 0, 'name' => ['et' => 'Esimene', 'en' => 'First']]);
        $second = Procedure::factory()->for($a)->create(['sort' => 2, 'name' => ['et' => 'Teine', 'en' => 'Second']]);
        $third = Procedure::factory()->for($b)->published()->create(['sort' => 0, 'name' => ['et' => 'Kolmas', 'en' => 'Third']]);
        $this->actingAs($this->admin())->get('/en/admin/procedures')->assertOk()->assertSeeInOrder(['First', 'Second', 'Third']);
        $this->get('/en/admin/procedures?category_id='.$b->id.'&status=published')->assertSee('Third')->assertDontSee('First');
        $this->get('/en/admin/procedures?search=Esimene')->assertDontSee('First');
        $this->get('/et/admin/procedures?search=Esimene')->assertSee('First')->assertDontSee('Second');
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_validation_keeps_dynamic_input_and_localizes_errors(): void
    {
        $data = $this->data();
        unset($data['name']['en'], $data['timeline_steps']['days_1_3']['body']['en']);
        $data['slug'] = 'Bad Slug';
        $data['advice_items']['do'][0]['text']['en'] = '';
        $data['advice_items']['do'][0]['merge_key'] = 'Bad/key';
        $this->actingAs($this->admin())->from('/et/admin/procedures/create')->post('/et/admin/procedures', $data)
            ->assertRedirect('/et/admin/procedures/create')
            ->assertSessionHasErrors(['name.en', 'slug', 'timeline_steps.days_1_3.body.en', 'advice_items.do.0.text.en', 'advice_items.do.0.merge_key'])
            ->assertSessionHasInput('advice_items.do.0.text.et', $data['advice_items']['do'][0]['text']['et']);
        $this->withCookie(config('session.cookie'), session()->getId())->get('/et/admin/procedures/create')->assertOk()
            ->assertSee($data['advice_items']['do'][0]['text']['et'])->assertDontSee('admin.procedure.');
    }

    public function test_duplicate_slug_and_incomplete_or_extra_timeline_are_rejected(): void
    {
        $procedure = $this->createProcedure();
        $data = $this->data();
        $data['slug'] = $procedure->slug;
        unset($data['timeline_steps']['week_1']);
        $this->post('/en/admin/procedures', $data)->assertSessionHasErrors(['slug', 'timeline_steps', 'timeline_steps.week_1']);
        $data = $this->data();
        $data['timeline_steps']['extra'] = $data['timeline_steps']['week_1'];
        $this->post('/en/admin/procedures', $data)->assertSessionHasErrors('timeline_steps');
    }

    public static function doses(): array
    {
        return [['5 mg'], ['1,5 ml'], ['2 g'], ['10 µg'], ['10 mcg'], ['1 tabl'], ['1 tablett'], ['1 tablet'], ['2 tablets'], ['1 kapsel'], ['1 capsule'], ['2 korda päevas'], ['3 times a day'], ['2x daily']];
    }

    #[DataProvider('doses')]
    public function test_doses_are_rejected_in_both_languages_and_all_timeline_text(string $text): void
    {
        $data = $this->data();
        foreach (['et', 'en'] as $locale) {
            $data['advice_items']['pain'][0]['text'][$locale] = $text;
            foreach (['marker', 'title', 'body'] as $field) {
                $data['timeline_steps']['first_24h'][$field][$locale] = $text;
            }
        }
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasErrors([
            'advice_items.pain.0.text.et', 'advice_items.pain.0.text.en',
            'timeline_steps.first_24h.marker.et', 'timeline_steps.first_24h.title.en', 'timeline_steps.first_24h.body.en',
        ]);
        $this->assertDatabaseCount('procedures', 0);
    }

    public function test_status_workflow_and_content_edit_requires_new_review(): void
    {
        $procedure = $this->createProcedure();
        $data = $this->data($procedure);
        $data['status'] = 'published';
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasErrors('status');
        $data['status'] = 'reviewed';
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasErrors('reviewed_by');
        $data['reviewed_by'] = 'Clinic reviewer';
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors();
        $procedure->refresh();
        $this->assertSame(ProcedureStatus::Reviewed, $procedure->status);
        $this->assertNotNull($procedure->reviewed_at);
        $reviewedAt = $procedure->reviewed_at->toISOString();
        $data['status'] = 'published';
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(ProcedureStatus::Published, $procedure->fresh()->status);
        $data['sort'] = 10;
        $data['is_active'] = false;
        $data['category_id'] = (string) $data['category_id'];
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(ProcedureStatus::Published, $procedure->fresh()->status);
        $this->assertSame($reviewedAt, $procedure->fresh()->reviewed_at->toISOString());
        $data['summary']['en'] = 'Updated aftercare summary';
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors()->assertSessionHas('warning');
        $this->assertSame(ProcedureStatus::Draft, $procedure->fresh()->status);
        $this->assertNull($procedure->fresh()->reviewed_at);
        $this->assertNull($procedure->fresh()->reviewed_by);
    }

    public function test_publishing_minimums_apply_only_on_publish(): void
    {
        $data = $this->data();
        $data['advice_items'] = [];
        $data['status'] = 'reviewed';
        $data['reviewed_by'] = 'Clinic reviewer';
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $data['status'] = 'published';
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasErrors('advice_items.do');
        $this->assertSame(ProcedureStatus::Reviewed, $procedure->fresh()->status);
    }

    public function test_svg_is_sanitized_and_accepts_more_than_category_limit(): void
    {
        $data = $this->data();
        $data['illustration'] = $this->svg(str_replace('<rect', '<script>bad()</script><rect onload="bad()"', self::SVG).str_repeat(' ', 60 * 1024));
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $this->assertSame('illustrations/'.$procedure->category->slug.'/'.$procedure->slug.'.svg', $procedure->illustration_path);
        $stored = File::get(public_path($procedure->illustration_path));
        $this->assertStringNotContainsString('script', $stored);
        $this->assertStringNotContainsString('onload', $stored);
        $this->assertStringContainsString('viewBox="0 0 160 120"', $stored);
    }

    public function test_small_png_rejected_and_boundary_width_png_accepted(): void
    {
        $data = $this->data();
        $data['illustration'] = $this->png(708);
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasErrors(['illustration' => __('admin.procedure.png_small', [], 'en')]);
        $this->assertDatabaseCount('procedures', 0);
        $data['illustration'] = $this->png(709);
        $this->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $this->assertFileExists(public_path(Procedure::sole()->illustration_path));
    }

    public function test_invalid_replacement_keeps_old_file_and_large_uploads_are_rejected(): void
    {
        $data = $this->data();
        $data['illustration'] = $this->svg();
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $path = $procedure->illustration_path;
        $original = File::get(public_path($path));
        $data = $this->data($procedure);
        $data['slug'] .= '-renamed';
        foreach ([$this->svg('<!DOCTYPE svg><svg/>'), $this->svg(self::SVG.str_repeat(' ', 201 * 1024)), UploadedFile::fake()->create('large.png', 2049, 'image/png'), UploadedFile::fake()->createWithContent('fake.png', self::SVG), $this->png(708)] as $upload) {
            $data['illustration'] = $upload;
            $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasErrors('illustration');
            $this->assertSame($path, $procedure->fresh()->illustration_path);
            $this->assertSame($original, File::get(public_path($path)));
        }
    }

    public function test_successful_replacement_removes_old_file_and_destroy_cascades_but_keeps_saved_leaflet(): void
    {
        $data = $this->data();
        $data['illustration'] = $this->svg();
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $oldPath = public_path($procedure->illustration_path);
        $data = $this->data($procedure);
        $data['illustration'] = $this->png(1063);
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors();
        $path = public_path($procedure->fresh()->illustration_path);
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists($path);
        $saved = SavedLeaflet::factory()->create(['user_id' => auth()->id(), 'procedure_ids' => [$procedure->id]]);
        $this->delete('/en/admin/procedures/'.$procedure->id)->assertSessionHasNoErrors()->assertRedirect('/en/admin/procedures');
        $this->assertDatabaseCount('procedures', 0);
        $this->assertDatabaseCount('timeline_steps', 0);
        $this->assertDatabaseCount('advice_items', 0);
        $this->assertDatabaseHas('saved_leaflets', ['id' => $saved->id]);
        $this->assertFileDoesNotExist($path);
        $this->get('/en/account/leaflets')->assertOk()->assertSee(__('account.removed', [], 'en'));
    }

    public function test_database_failure_rolls_back_content_and_preserves_illustration(): void
    {
        $data = $this->data();
        $data['illustration'] = $this->svg();
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $original = File::get(public_path($procedure->illustration_path));
        $data = $this->data($procedure);
        $data['illustration'] = $this->png(709);
        AdviceItem::creating(function (): void {
            throw new RuntimeException('Simulated database failure');
        });
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertServerError();
        $this->assertSame($original, File::get(public_path($procedure->fresh()->illustration_path)));
        $this->assertDatabaseCount('timeline_steps', 4);
        $this->assertDatabaseCount('advice_items', 8);
    }

    public static function contentEdits(): array
    {
        return [['timeline'], ['advice'], ['merge_key'], ['review_note'], ['color'], ['reorder']];
    }

    #[DataProvider('contentEdits')]
    public function test_edits_to_reviewed_content_reset_to_draft(string $edit): void
    {
        $procedure = Procedure::factory()->withContent()->create(['status' => ProcedureStatus::Reviewed, 'reviewed_by' => 'Reviewer', 'reviewed_at' => now()]);
        $data = $this->data($procedure);
        switch ($edit) {
            case 'timeline':
                $data['timeline_steps']['week_1']['title']['et'] = 'Muudetud';
                break;
            case 'advice':
                $data['advice_items']['pain'][0]['text']['en'] = 'Updated advice';
                break;
            case 'merge_key':
                $data['advice_items']['pain'][0]['merge_key'] = 'general.new_key';
                break;
            case 'review_note':
                $data['review_note']['en'] = 'Updated review note';
                break;
            case 'color':
                $data['color'] = '#aabbcc';
                break;
            case 'reorder':
                $data['advice_items']['do'][0]['text']['en'] = 'First distinct item';
                // Establish distinct content before review, then reverse it.
                $procedure->adviceItems->where('section', AdviceSection::Do)->first()->update(['text' => $data['advice_items']['do'][0]['text']]);
                $data['advice_items']['do'] = array_reverse($data['advice_items']['do']);
                break;
        }
        $this->actingAs($this->admin())->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors()->assertSessionHas('warning');
        $this->assertSame(ProcedureStatus::Draft, $procedure->fresh()->status);
        if ($edit === 'reorder') {
            $texts = $procedure->fresh()->adviceFor(AdviceSection::Do)->get()->map(fn ($item) => $item->getTranslation('text', 'en'))->all();
            $this->assertSame(array_column(array_column($data['advice_items']['do'], 'text'), 'en'), $texts);
        }
    }

    public function test_sort_only_of_existing_content_with_per_section_sort_keeps_review(): void
    {
        $procedure = Procedure::factory()->withContent()->create(['status' => ProcedureStatus::Reviewed, 'reviewed_by' => 'Reviewer', 'reviewed_at' => now()]);
        $data = $this->data($procedure);
        $data['sort'] = 99;
        $this->actingAs($this->admin())->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(ProcedureStatus::Reviewed, $procedure->fresh()->status);
    }

    public function test_rename_and_category_change_move_illustration_and_failure_restores_both(): void
    {
        $data = $this->data();
        $data['illustration'] = $this->svg();
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $source = public_path($procedure->illustration_path);
        $original = File::get($source);
        $data = $this->data($procedure);
        $data['slug'] = 'renamed';
        $category = Category::factory()->create();
        $data['category_id'] = $category->id;
        $destination = public_path('illustrations/'.$category->slug.'/renamed.svg');
        $filesystem = File::getFacadeRoot();
        File::partialMock()->shouldReceive('delete')->once()->with($source)->andReturn(false);
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasErrors('illustration');
        $this->assertSame($original, File::get($source));
        $this->assertFileDoesNotExist($destination);
        $this->assertNotSame('renamed', $procedure->fresh()->slug);
        File::swap($filesystem);
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors();
        $this->assertSame($original, File::get($destination));
        $this->assertFileDoesNotExist($source);
    }

    public function test_empty_no_javascript_rows_and_blank_review_notes_are_ignored(): void
    {
        $data = $this->data();
        $data['review_note'] = ['et' => '', 'en' => ''];
        foreach (AdviceSection::cases() as $section) {
            $data['advice_items'][$section->value] = [
                ['text' => ['et' => '', 'en' => ''], 'merge_key' => ''],
                ['text' => ['et' => '', 'en' => ''], 'merge_key' => ''],
            ];
        }
        $this->actingAs($this->admin())->post('/en/admin/procedures', $data)->assertSessionHasNoErrors();
        $procedure = Procedure::sole();
        $this->assertDatabaseCount('advice_items', 0);
        $data['status'] = 'reviewed';
        $data['reviewed_by'] = 'Reviewer';
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors();
        $data['sort'] = 15;
        $this->put('/en/admin/procedures/'.$procedure->id, $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(ProcedureStatus::Reviewed, $procedure->fresh()->status);
    }

    public function test_all_admin_translation_keys_match(): void
    {
        $et = Arr::dot(require lang_path('et/admin.php'));
        $en = Arr::dot(require lang_path('en/admin.php'));
        $this->assertSame(array_keys($et), array_keys($en));
        foreach ($et as $value) {
            $this->assertStringNotContainsString("\u{fffd}", $value);
        }
    }
}
