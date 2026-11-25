<?php

namespace Tests\Security;

use App\Enums\LeafletFormat;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\OfflinePdfHttpClient;
use App\Services\Leaflet\OnlineLeafletUrl;
use App\Services\Leaflet\PdfFailureResponse;
use App\Services\Leaflet\PdfIcons;
use App\Services\Leaflet\PdfRenderer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mpdf\PsrHttpMessageShim\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

final class PdfPrivacyTest extends SecurityTestCase
{
    public static function remoteResources(): array
    {
        return [['https://example.invalid/private.png'], ['http://127.0.0.1/private'], ['file:///nonexistent'], ['http://169.254.169.254/']];
    }

    #[DataProvider('remoteResources')]
    public function test_offline_transport_throws_without_reading_any_resource(string $uri): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new OfflinePdfHttpClient)->sendRequest(new Request('GET', $uri));
    }

    public function test_real_mpdf_fetcher_cannot_read_private_local_files(): void
    {
        $path = storage_path('framework/testing/security-private.svg');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, '<svg xmlns="http://www.w3.org/2000/svg"><title>private marker</title></svg>');
        try {
            $mpdf = app(PdfRenderer::class)->createMpdf();
            $fetcher = (new \ReflectionProperty($mpdf, 'assetFetcher'))->getValue($mpdf);
            $this->expectException(RuntimeException::class);
            $fetcher->fetchDataFromPath($path);
        } finally {
            File::delete($path);
        }
    }

    public function test_stored_category_svg_is_sanitized_before_raw_pdf_markup(): void
    {
        $root = storage_path('framework/testing/security-icon-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($root.'/icons/categories');
        $this->app->usePublicPath($root);
        File::put($root.'/icons/categories/security.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>bad()</script><path onload="bad()" d="M0 0"/></svg>');
        try {
            $html = app(PdfIcons::class)->category('security', '#123456');
            preg_match('/base64,([^" ]+)/', $html, $match);
            $svg = base64_decode($match[1]);
            $this->assertStringNotContainsString('script', $svg);
            $this->assertStringNotContainsString('onload', $svg);
            $this->assertStringContainsString('<path', $svg);
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_personal_data_is_absent_from_pdf_metadata_headers_and_public_url(): void
    {
        Log::spy();
        $procedure = Procedure::factory()->withContent()->create();
        $user = User::factory()->create(['name' => 'UniquePatient', 'email' => 'unique-patient@example.test']);
        $leaflet = SavedLeaflet::factory()->for($user)->create(['procedure_ids' => [$procedure->id], 'first_name' => $user->name, 'locale' => 'en']);
        $response = $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => '192.0.2.123'])
            ->get('/en/account/leaflets/'.$leaflet->uuid.'/pdf?'.http_build_query(['filename' => "bad\r\nX-Injected: yes", 'first_name' => 'untrusted']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeaderMissing('X-Injected');
        $headers = json_encode($response->headers->all());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString(LeafletFormat::A5->filename('en'), $response->headers->get('Content-Disposition'));
        $pdf = $response->streamedContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        // Inspect the PDF's actual Info dictionary, including its UTF-16 strings.
        preg_match('/\/Info\s+(\d+)\s+0\s+R/', $pdf, $info);
        $this->assertNotEmpty($info);
        preg_match('/\b'.preg_quote($info[1], '/').' 0 obj(.*?)endobj/s', $pdf, $metadata);
        $this->assertNotEmpty($metadata);
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'en', $user->name, '2026-11-24');
        $url = (new OnlineLeafletUrl($doc))->onlineUrl();
        $this->assertSame(route('online.show', ['locale' => 'en', 'combo' => $procedure->slug]), $url);
        foreach ([$user->name, $user->email, '192.0.2.123', '2026-11-24'] as $secret) {
            $this->assertStringNotContainsString($secret, $headers);
            $this->assertStringNotContainsString($secret, $url);
            $this->assertStringNotContainsString($secret, $metadata[1]);
            $this->assertStringNotContainsString(mb_convert_encoding($secret, 'UTF-16BE', 'UTF-8'), $metadata[1]);
        }
        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'log'] as $method) {
            Log::shouldNotHaveReceived($method);
        }
    }

    public function test_pdf_failure_logs_only_exception_type_and_never_patient_text(): void
    {
        Log::spy();
        $secret = 'PatientSecret patient@example.test 192.0.2.123';
        app(PdfFailureResponse::class)->render(new RuntimeException($secret));
        Log::shouldHaveReceived('error')->once()->with('PDF rendering failed.', ['exception_type' => RuntimeException::class]);
    }
}
