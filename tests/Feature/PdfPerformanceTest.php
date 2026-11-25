<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use App\Services\Leaflet\PdfRenderer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_c_renders_within_six_seconds(): void
    {
        $this->seed(DatabaseSeeder::class);
        $ids = Procedure::whereIn('slug', ['tooth-extraction', 'mole-removal', 'knee-arthroscopy'])->pluck('id')->all();
        $doc = app(LeafletBuilder::class)->build($ids, 'et', null, null);
        $this->assertCount(3, $doc->sections);

        $start = microtime(true);
        $pdf = app(PdfRenderer::class)->render($doc);
        $seconds = microtime(true) - $start;

        fwrite(STDOUT, sprintf("\nSample c PDF render: %.3f s\n", $seconds));
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertLessThan(6, $seconds, 'Three-procedure PDF exceeded the CI render budget.');
    }
}
