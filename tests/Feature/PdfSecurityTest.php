<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\OfflinePdfHttpClient;
use App\Services\Leaflet\PdfRenderer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mpdf\PsrHttpMessageShim\Request;
use ReflectionMethod;
use Tests\TestCase;

class PdfSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_transport_denies_requests_and_production_disables_image_errors(): void
    {
        $this->assertSame(403, (new OfflinePdfHttpClient)->sendRequest(new Request('GET', 'https://example.invalid/image.png'))->getStatusCode());
        $this->app->instance('env', 'production');
        $mpdf = (new ReflectionMethod(PdfRenderer::class, 'createMpdf'))->invoke(app(PdfRenderer::class));
        $this->assertFalse($mpdf->curlAllowUnsafeSslRequests);
        $this->assertFalse($mpdf->showImageErrors);
        $fetcher = (new \ReflectionProperty($mpdf, 'assetFetcher'))->getValue($mpdf);
        $this->assertSame('', $fetcher->fetchRemoteContent('https://example.invalid/image.png'));
    }

    public function test_personal_name_is_escaped_before_rendering_pdf_html(): void
    {
        $procedure = Procedure::factory()->withContent()->create();
        $name = '<img src="https://example.invalid/name.png">';
        $doc = app(LeafletBuilder::class)->build([$procedure->id], 'en', $name, null);
        $html = view('pdf.a5.document', ['doc' => $doc, 'settings' => app(Settings::class), 'printMode' => false, 'categoryProof' => false, 'notesPages' => 0])->render();
        $this->assertStringNotContainsString($name, $html);
        $this->assertStringContainsString(e($name), $html);
    }
}
