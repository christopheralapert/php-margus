<?php

namespace Tests\Feature;

use App\Enums\AdviceSection;
use App\Models\Consent;
use App\Models\Procedure;
use App\Models\User;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeafletTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et', '24.11.2026', 'jarelravi-voldik.pdf'], ['en', '24 Nov 2026', 'aftercare-leaflet.pdf']];
    }

    public function test_wizard_pages_and_download_require_an_active_selection(): void
    {
        foreach (['details', 'preview'] as $page) {
            $this->get('/et/leaflet/'.$page)->assertRedirect('/et/leaflet');
        }
        $this->post('/et/leaflet/pdf')->assertRedirect('/et/leaflet');
        $inactive = Procedure::factory()->create(['is_active' => false]);
        $this->withSession(['wizard.procedures' => [$inactive->id]])
            ->get('/et/leaflet/details')->assertRedirect('/et/leaflet');
        $procedure = Procedure::factory()->create();
        $this->withSession(['wizard.procedures' => [$procedure->id]])
            ->get('/et/leaflet/details')->assertOk()->assertSee($procedure->name)
            ->assertSee('name="consent"', false)->assertSee(__('wizard.consent_help'));
    }

    public static function invalidDetails(): array
    {
        return [
            [['first_name' => str_repeat('a', 41), 'consent' => 1], 'first_name'],
            [['first_name' => 'Mari2', 'consent' => 1], 'first_name'],
            [['first_name' => '<script>', 'consent' => 1], 'first_name'],
            [['first_name' => ['Mari'], 'consent' => 1], 'first_name'],
            [['date' => '26.11.2026', 'consent' => 1], 'date'],
            [['date' => '23.11.2025', 'consent' => 1], 'date'],
            [['date' => '30.02.2026', 'consent' => 1], 'date'],
            [['first_name' => 'Mari'], 'consent'],
            [['date' => '24.11.2026'], 'consent'],
        ];
    }

    #[DataProvider('invalidDetails')]
    public function test_invalid_details_are_rejected(array $input, string $field): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $procedure = Procedure::factory()->create();
        $response = $this->withSession(['wizard.procedures' => [$procedure->id]])
            ->from('/et/leaflet/details')->post('/et/leaflet/details', $input)
            ->assertRedirect('/et/leaflet/details')->assertSessionHasErrors($field);
        if ($field === 'consent') {
            $response->assertSessionHasErrors(['consent' => __('wizard.validation.consent', [], 'et')]);
        }
        $this->assertDatabaseCount('consents', 0);
    }

    public function test_personal_details_record_minimal_consent_and_retain_values_on_validation_error(): void
    {
        $procedure = Procedure::factory()->create();
        $this->withSession(['wizard.procedures' => [$procedure->id]])
            ->post('/et/leaflet/details', ['first_name' => "Õäöüšž O'Neil-Anne", 'consent' => 1])
            ->assertRedirect('/et/leaflet/preview')->assertSessionHasNoErrors()
            ->assertSessionHas('wizard.first_name', "Õäöüšž O'Neil-Anne")->assertSessionHas('wizard.consent', true);
        $this->assertDatabaseCount('consents', 1);
        $this->assertDatabaseHas('consents', [
            'purpose' => 'personalised_leaflet', 'user_id' => null,
            'text_version' => config('leaflet.consent_version'), 'locale' => 'et',
        ]);
        $this->assertNotNull(Consent::first()->given_at);
        foreach (['ip', 'ip_address', 'user_agent', 'first_name', 'date'] as $column) {
            $this->assertFalse(Schema::hasColumn('consents', $column));
        }
        $this->from('/et/leaflet/details')->post('/et/leaflet/details', ['first_name' => 'Mari2'])
            ->assertSessionHasInput('first_name', 'Mari2');
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/et/leaflet/details')->assertSee('value="Mari2"', false)->assertSee('aria-invalid="true"', false);
    }

    public function test_anonymous_details_need_no_consent_and_clear_previous_personal_values(): void
    {
        $procedure = Procedure::factory()->create();
        $this->withSession(['wizard' => ['procedures' => [$procedure->id], 'first_name' => 'Mari', 'date' => '2026-11-24', 'consent' => true]])
            ->post('/et/leaflet/details', [])
            ->assertRedirect('/et/leaflet/preview')->assertSessionHasNoErrors()
            ->assertSessionHas('wizard.consent', false);
        $this->assertNull(session('wizard.first_name'));
        $this->assertNull(session('wizard.date'));
        $this->assertDatabaseCount('consents', 0);
    }

    public function test_date_boundaries_and_authenticated_consent(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 24));
        $user = User::factory()->create();
        $procedure = Procedure::factory()->create();
        foreach (['24/11/2025', '25/11/2026'] as $date) {
            $this->actingAs($user)->withSession(['wizard.procedures' => [$procedure->id]])
                ->post('/en/leaflet/details', ['date' => $date, 'consent' => 1])
                ->assertRedirect('/en/leaflet/preview')->assertSessionHasNoErrors();
        }
        $this->assertDatabaseHas('consents', ['user_id' => $user->id, 'locale' => 'en']);
    }

    #[DataProvider('locales')]
    public function test_preview_uses_selected_order_localized_dates_and_all_content(string $locale, string $date, string $filename): void
    {
        $procedures = Procedure::factory()->count(2)->withContent()->sequence(
            ['name' => ['et' => 'Esimene ravi', 'en' => 'First treatment']],
            ['name' => ['et' => 'Teine ravi', 'en' => 'Second treatment']],
        )->create();
        app(Settings::class)->set('clinic.phone', '+372 555 1234');
        app(Settings::class)->set('clinic.after_hours_phone', '+372 555 9876');
        $response = $this->withSession(['wizard' => ['procedures' => $procedures->reverse()->modelKeys(), 'date' => '2026-11-24', 'first_name' => 'Mari', 'consent' => true]])
            ->get('/'.$locale.'/leaflet/preview')->assertOk()
            ->assertSeeInOrder([$procedures[1]->getTranslation('name', $locale), $procedures[0]->getTranslation('name', $locale)])
            ->assertSee($date)->assertSee('Mari')->assertSee('data-red-flags', false)
            ->assertSee('+372 555 1234')->assertSee('+372 555 9876')->assertSee('112')
            ->assertSee(__('wizard.draft', [], $locale))->assertSee('name="_token"', false);
        foreach (AdviceSection::cases() as $section) {
            $response->assertSee(__('enums.advice_section.'.$section->value, [], $locale));
        }
        foreach ($procedures[0]->timelineSteps as $step) {
            $response->assertSee($step->getTranslation('marker', $locale))->assertSee($step->getTranslation('title', $locale))->assertSee($step->getTranslation('body', $locale));
        }
        $response->assertSee($procedures[0]->getTranslation('follow_up', $locale));
    }

    #[DataProvider('locales')]
    public function test_pdf_is_downloaded_in_memory_and_clears_wizard(string $locale, string $date, string $filename): void
    {
        $before = File::allFiles(storage_path('app'));
        $pathsBefore = array_map(fn ($file) => $file->getPathname(), $before);
        Storage::fake('local');
        $procedure = Procedure::factory()->withContent()->create(['name' => ['et' => 'õäöüšž', 'en' => 'õäöüšž']]);
        $response = $this->withSession(['wizard' => ['procedures' => [$procedure->id], 'first_name' => 'Mari', 'date' => '2026-11-24', 'consent' => true]])
            ->post('/'.$locale.'/leaflet/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->assertSessionMissing('wizard');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        Storage::disk('local')->assertDirectoryEmpty('/');
        $this->assertSame($pathsBefore, array_map(fn ($file) => $file->getPathname(), File::allFiles(storage_path('app'))));
        $this->assertSame('sourcesans3', PdfRenderer::DEFAULT_FONT);
        $this->assertMatchesRegularExpression('/\/BaseFont \/[A-Z]+\+SourceSans3-Regular/', $response->getContent());
        $this->assertTrue(str_contains($response->getContent(), '/Lang ('.$locale.')'));
    }
}
