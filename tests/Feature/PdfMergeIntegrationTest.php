<?php

namespace Tests\Feature;

use App\Enums\LeafletFormat;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use App\Services\Leaflet\LeafletDocument;
use App\Services\Leaflet\RendersPdf;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PdfMergeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public static function downloads(): array
    {
        $cases = [];
        foreach (['wizard', 'account', 'admin'] as $endpoint) {
            foreach (LeafletFormat::cases() as $format) {
                foreach ([false, true] as $print) {
                    $cases[] = [$endpoint, $format, $print];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('downloads')]
    public function test_downloads_pass_format_and_print_mode_through_the_shared_helper(string $endpoint, LeafletFormat $format, bool $print): void
    {
        [$url, $leaflet, $procedure] = $this->endpoint($endpoint);
        $this->mock(RendersPdf::class)->shouldReceive('render')->once()
            ->withArgs(fn (LeafletDocument $doc, bool $printMode, bool $categoryProof, LeafletFormat $selected) => $doc->locale === 'en' && $printMode === $print && ! $categoryProof && $selected === $format)
            ->andReturn('%PDF-test');
        $params = ['format' => $format->value, 'print' => $print ? '1' : '0'];
        $response = $endpoint === 'wizard'
            ? $this->post($url.'?'.http_build_query($params))
            : $this->get($url.'?'.http_build_query($params));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $filename = $format->filename('en', $endpoint === 'admin' ? 'preview-'.$procedure->slug.'-en' : null);
        $this->assertStringContainsString($filename, $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith($endpoint === 'admin' ? 'inline;' : 'attachment;', $response->headers->get('Content-Disposition'));
        $this->assertSame('%PDF-test', $endpoint === 'account' ? $response->streamedContent() : $response->getContent());
        if ($endpoint === 'wizard') {
            $response->assertSessionMissing('wizard');
        } elseif ($endpoint === 'account') {
            $this->assertNotNull($leaflet->fresh()->last_downloaded_at);
        }
    }

    public static function failures(): array
    {
        return [['wizard', 'et'], ['account', 'et'], ['admin', 'et'], ['wizard', 'en'], ['account', 'en'], ['admin', 'en']];
    }

    #[DataProvider('failures')]
    public function test_trifold_failures_preserve_retry_state_and_hide_patient_data(string $endpoint, string $locale): void
    {
        [$url, $leaflet, $procedure] = $this->endpoint($endpoint, $locale);
        $this->mock(RendersPdf::class)->shouldReceive('render')->once()
            ->withArgs(fn (LeafletDocument $doc, bool $print, bool $proof, LeafletFormat $format) => $format === LeafletFormat::Trifold)
            ->andThrow(new RuntimeException('PrivatePatient confidential'));
        Log::shouldReceive('error')->once()->with('PDF rendering failed.', ['exception_type' => RuntimeException::class]);
        $url .= '?format=trifold';
        $response = $endpoint === 'wizard' ? $this->post($url) : $this->get($url);
        $response->assertStatus(500)->assertSee(__('errors.pdf.title', [], $locale))
            ->assertSee(config('clinic.clinic.phone'))->assertDontSee('PrivatePatient')->assertDontSee('confidential');
        if ($endpoint === 'wizard') {
            $response->assertSessionHas('wizard.procedures', [$procedure->id]);
        } elseif ($endpoint === 'account') {
            $this->assertNull($leaflet->fresh()->last_downloaded_at);
        }
    }

    public function test_preview_trifold_control_is_a_keyboard_accessible_post_form_under_csp(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $response = $this->withSession(['wizard.procedures' => [$procedure->id]])->get('/en/leaflet/preview')->assertOk();
        $this->assertDoesNotMatchRegularExpression('/script-src[^;]*\x27unsafe-inline\x27/', $response->headers->get('Content-Security-Policy'));
        $dom = new DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new DOMXPath($dom);
        $forms = $xpath->query('//form[input[@name="format" and @value="trifold"]]');
        $this->assertCount(1, $forms);
        $form = $forms->item(0);
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame(route('leaflet.pdf', ['locale' => 'en']), $form->getAttribute('action'));
        $this->assertCount(1, $xpath->query('input[@name="_token"]', $form));
        $button = $xpath->query('button[@type="submit"]', $form)->item(0);
        $this->assertStringContainsString('focus-visible:ring-2', $button->getAttribute('class'));
        $this->assertStringContainsString('min-h-14', $button->getAttribute('class'));
        $this->assertSame(__('wizard.download_trifold', [], 'en'), $button->textContent);
        $this->assertFalse($button->hasAttribute('onclick'));
        $this->assertCount(0, $xpath->query('.//script', $form));
    }

    private function endpoint(string $endpoint, string $locale = 'en'): array
    {
        $procedure = Procedure::factory()->withContent()->create();
        $user = User::factory()->create(['is_admin' => true]);
        $leaflet = SavedLeaflet::factory()->for($user)->create(['procedure_ids' => [$procedure->id], 'locale' => $locale]);
        $this->actingAs($user)->withSession(['wizard' => ['procedures' => [$procedure->id], 'first_name' => 'PrivatePatient']]);
        $path = match ($endpoint) {
            'wizard' => '/leaflet/pdf',
            'account' => '/account/leaflets/'.$leaflet->uuid.'/pdf',
            'admin' => '/admin/procedures/'.$procedure->id.'/preview.pdf',
        };

        return ['/'.$locale.$path, $leaflet, $procedure];
    }
}
