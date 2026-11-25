<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use App\Services\Leaflet\LeafletDocument;
use App\Services\Leaflet\RendersPdf;
use App\Services\Leaflet\ThemeColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PolishTest extends TestCase
{
    use RefreshDatabase;

    public static function locales(): array
    {
        return [['et'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_expired_preview_and_pdf_redirect_with_a_localized_message(string $locale): void
    {
        $this->get('/'.$locale.'/leaflet/preview')->assertRedirect('/'.$locale.'/leaflet')
            ->assertSessionHas('status', __('wizard.session_expired', [], $locale));
        $this->post('/'.$locale.'/leaflet/pdf')->assertRedirect('/'.$locale.'/leaflet')
            ->assertSessionHas('status', __('wizard.session_expired', [], $locale));
    }

    #[DataProvider('locales')]
    public function test_pdf_failures_are_friendly_keep_selection_and_log_no_patient_input(string $locale): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $this->mock(RendersPdf::class)->shouldReceive('render')->once()
            ->withArgs(fn (LeafletDocument $doc) => $doc->firstName === 'PrivatePatient')
            ->andThrow(new RuntimeException('PrivatePatient sensitive content'));
        Log::shouldReceive('error')->once()->with('PDF rendering failed.', ['exception_type' => RuntimeException::class]);

        $this->withSession(['wizard' => ['procedures' => [$procedure->id], 'first_name' => 'PrivatePatient']])
            ->post('/'.$locale.'/leaflet/pdf')->assertStatus(500)
            ->assertSee(__('errors.pdf.title', [], $locale))
            ->assertSee(config('clinic.clinic.phone'))->assertSee('tel:112', false)
            ->assertDontSee('PrivatePatient')->assertDontSee('sensitive content')
            ->assertSessionHas('wizard.procedures', [$procedure->id]);
    }

    public function test_saved_and_admin_pdf_failures_use_the_same_safe_response(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $procedure = Procedure::factory()->withContent()->create();
        $leaflet = SavedLeaflet::factory()->for($user)->create(['procedure_ids' => [$procedure->id]]);
        $this->mock(RendersPdf::class)->shouldReceive('render')->twice()->andThrow(new RuntimeException('Private input'));
        Log::shouldReceive('error')->twice()->with('PDF rendering failed.', ['exception_type' => RuntimeException::class]);

        $this->actingAs($user)->get('/et/account/leaflets/'.$leaflet->uuid.'/pdf')->assertStatus(500)->assertSee(__('errors.pdf.title'));
        $this->get('/et/admin/procedures/'.$procedure->id.'/preview.pdf')->assertStatus(500)->assertSee(__('errors.pdf.title'));
        $this->assertNull($leaflet->fresh()->last_downloaded_at);
    }

    #[DataProvider('locales')]
    public function test_empty_picker_provides_a_phone_and_no_continue_button(string $locale): void
    {
        $this->get('/'.$locale.'/leaflet')->assertOk()->assertSee(__('wizard.empty', [], $locale))
            ->assertSee('tel:'.str_replace(' ', '', config('clinic.clinic.phone')), false)
            ->assertDontSee(__('wizard.continue', [], $locale));
    }

    public function test_pages_have_skip_target_landmarks_and_one_main_heading(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        foreach (['/et', '/et/privacy', '/et/leaflet', '/et/login', '/et/register', '/et/forgot-password'] as $path) {
            $this->assertAccessibleShell($path);
        }
        $this->withSession(['wizard.procedures' => [$procedure->id]]);
        foreach (['/et/leaflet/details', '/et/leaflet/preview', '/et/l/'.$procedure->slug] as $path) {
            $this->assertAccessibleShell($path);
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach (['/et/dashboard', '/et/profile', '/et/account/leaflets', '/et/admin', '/et/admin/categories', '/et/admin/categories/create', '/et/admin/procedures', '/et/admin/procedures/create', '/et/admin/settings'] as $path) {
            $this->assertAccessibleShell($path);
        }
    }

    public function test_auth_errors_are_associated_with_their_controls(): void
    {
        $this->from('/en/login')->post('/en/login', ['email' => 'invalid'])->assertSessionHasErrors('email');
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/en/login')->assertOk()->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="email-error"', false)->assertSee('id="email-error"', false);
    }

    public function test_admin_procedure_errors_are_associated_and_translations_stay_visible(): void
    {
        Procedure::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->from('/en/admin/procedures/create')->post('/en/admin/procedures', ['slug' => 'Invalid slug'])
            ->assertSessionHasErrors('slug');
        $html = $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/en/admin/procedures/create')->assertOk()->assertSee('id="procedure-errors"', false)->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*aria-describedby="procedure-errors"[^>]*aria-invalid="true"[^>]*name="slug"/', $html);
        $this->assertStringContainsString('tab === null || tab ===', $html);
    }

    public function test_brand_text_colours_meet_aa_on_white(): void
    {
        foreach (['primary', 'secondary', 'accent', 'danger'] as $name) {
            $theme = new ThemeColor(config('clinic.brand.colors.'.$name));
            $this->assertGreaterThanOrEqual(4.5, ThemeColor::contrast($theme->dark(), '#FFFFFF'), $name);
        }
    }

    private function assertAccessibleShell(string $path): void
    {
        $html = $this->get($path)->assertOk()->assertSee('href="#main"', false)
            ->assertSee('id="main" tabindex="-1"', false)->getContent();
        $this->assertSame(1, preg_match_all('/<h1\b/', $html), $path);
        foreach (['header', 'nav', 'main', 'footer'] as $landmark) {
            $this->assertMatchesRegularExpression('/<'.$landmark.'\b/', $html, $path);
        }
    }
}
