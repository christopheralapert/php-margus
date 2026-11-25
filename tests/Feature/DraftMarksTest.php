<?php

namespace Tests\Feature;

use App\Enums\LeafletFormat;
use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Models\User;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DraftMarksTest extends TestCase
{
    use RefreshDatabase;

    public static function localesAndVisibility(): array
    {
        return [['et', true], ['en', true], ['et', false], ['en', false]];
    }

    #[DataProvider('localesAndVisibility')]
    public function test_patient_marks_follow_the_switch_without_changing_content(string $locale, bool $visible): void
    {
        $this->assertTrue(config('leaflet.show_draft_marks'));
        config(['leaflet.show_draft_marks' => $visible]);
        $procedure = Procedure::factory()->draft()->withContent()->create();
        $reviewNote = $procedure->getTranslations('review_note');
        $doc = app(LeafletBuilder::class)->build([$procedure->id], $locale, null, null);
        $this->assertTrue($doc->hasDraft());
        $this->assertSame($visible, $doc->showDraftMarks());
        $settings = app(Settings::class);

        $html = view('pdf.a5.document', ['doc' => $doc, 'settings' => $settings, 'printMode' => false])->render();
        $this->assertSame($visible, str_contains($html, __('pdf.draft_notice', [], $locale)));
        $this->assertSame($visible, str_contains($html, '<div class="draft-notice'));
        $this->assertStringContainsString(e($settings->localized('leaflet.disclaimer', $locale)), $html);
        $panel = view('pdf.trifold.panel', [
            'doc' => $doc, 'settings' => $settings, 'kind' => 'cover', 'blocks' => [], 'artWidth' => 60,
            'truncated' => false,
        ])->render();
        $this->assertSame($visible, str_contains($panel, __('pdf.draft_notice', [], $locale)));
        $minimal = view('pdf.minimal', compact('doc'))->render();
        $this->assertSame($visible, str_contains($minimal, __('wizard.draft', [], $locale)));

        foreach (LeafletFormat::cases() as $format) {
            $pdf = app(PdfRenderer::class)->render($doc, format: $format);
            $this->assertSame($visible, preg_match('/\/ca 0\.06\b/', $pdf) === 1, $format->value);
        }

        $preview = $this->withSession(['wizard.procedures' => [$procedure->id]])
            ->get('/'.$locale.'/leaflet/preview')->assertOk();
        $online = $this->get(route('online.show', ['locale' => $locale, 'combo' => $procedure->slug]))->assertOk();
        foreach ([$preview, $online] as $response) {
            $this->assertSame($visible, str_contains($response->getContent(), __('wizard.draft', [], $locale)));
            $response->assertSee($settings->localized('leaflet.disclaimer', $locale));
        }
        $privacy = $this->get('/'.$locale.'/privacy')->assertOk();
        $this->assertSame($visible, str_contains($privacy->getContent(), __('privacy.draft', [], $locale)));
        $this->assertSame(ProcedureStatus::Draft, $procedure->fresh()->status);
        $this->assertSame($reviewNote, $procedure->fresh()->getTranslations('review_note'));
    }

    public static function formats(): array
    {
        return [['a5'], ['trifold']];
    }

    #[DataProvider('formats')]
    public function test_admin_preview_keeps_marks_when_patient_marks_are_disabled(string $format): void
    {
        config(['leaflet.show_draft_marks' => false, 'leaflet.draft_watermark' => false]);
        $procedure = Procedure::factory()->draft()->withContent()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/et/admin/procedures')->assertOk()->assertSee(ProcedureStatus::Draft->label());
        $response = $this->get('/et/admin/procedures/'.$procedure->id.'/preview.pdf?format='.$format)->assertOk();
        $this->assertMatchesRegularExpression('/\/ca 0\.06\b/', $response->getContent());

        // Staff previews must not leak their override into later patient requests.
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'et', null, null);
        $this->assertFalse($doc->showDraftMarks());
        $this->get(route('online.show', ['locale' => 'et', 'combo' => $procedure->slug]))
            ->assertOk()->assertDontSee(__('wizard.draft', [], 'et'));
    }

    public function test_admin_override_still_requires_draft_content(): void
    {
        config(['leaflet.show_draft_marks' => false]);
        $procedure = Procedure::factory()->published()->create();
        $builder = app(LeafletBuilder::class);
        $this->assertFalse($builder->build([$procedure->id], 'et', null, null, forceDraftMarks: true)->showDraftMarks());
        $this->assertFalse($builder->build([], 'et', null, null, forceDraftMarks: true)->showDraftMarks());
    }
}
