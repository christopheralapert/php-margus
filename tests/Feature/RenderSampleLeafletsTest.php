<?php

namespace Tests\Feature;

use App\Models\Procedure;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenderSampleLeafletsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_rendering_preserves_the_database_settings_and_clock(): void
    {
        $procedure = Procedure::factory()->create();
        $settings = app(Settings::class);
        $settings->set('clinic.name', ['et' => 'Alles', 'en' => 'Preserved']);
        $this->travelTo(now()->setDate(2025, 3, 2));
        $now = now()->toIso8601String();
        $immutableNow = CarbonImmutable::now()->toIso8601String();
        $connection = DB::getDefaultConnection();
        $pdo = DB::connection()->getPdo();

        $this->artisan('leaflet:render-samples', ['--only' => 'a-dental-et', '--print' => true])->assertSuccessful();
        $pdf = file_get_contents(base_path('tests/visual/output/a-dental-et.pdf'));
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('/MediaBox [0 0 476.22', $pdf);
        $this->assertSame($connection, DB::getDefaultConnection());
        $this->assertSame($pdo, DB::connection()->getPdo());
        $this->assertDatabaseCount('procedures', 1);
        $this->assertDatabaseHas('procedures', ['id' => $procedure->id, 'slug' => $procedure->slug]);
        $this->assertSame($settings, app(Settings::class));
        $this->assertSame('Alles', $settings->localized('clinic.name', 'et'));
        $this->assertSame($now, now()->toIso8601String());
        $this->assertSame($immutableNow, CarbonImmutable::now()->toIso8601String());
    }

    public function test_unknown_sample_fails_and_restores_the_connection(): void
    {
        $connection = DB::getDefaultConnection();
        $this->artisan('leaflet:render-samples', ['--only' => '../invalid'])->assertExitCode(2);
        $this->assertSame($connection, DB::getDefaultConnection());
        $this->assertDatabaseCount('procedures', 0);
    }

    public function test_icon_sample_can_be_rendered_on_its_own(): void
    {
        $this->artisan('leaflet:render-samples', ['--only' => 'icons'])->assertSuccessful();
        $this->assertStringStartsWith('%PDF', file_get_contents(base_path('tests/visual/output/icons.pdf')));
        $this->assertDatabaseCount('procedures', 0);
    }

    public function test_category_proof_has_exactly_one_page_per_category(): void
    {
        $this->artisan('leaflet:render-samples', ['--only' => 'categories'])->assertSuccessful();
        $pdf = file_get_contents(base_path('tests/visual/output/categories.pdf'));
        preg_match('/\/Type \/Pages\s*\/Kids\s*\[[^]]*\]\s*\/Count (\d+)/s', $pdf, $pageCount);
        $this->assertSame(9, (int) ($pageCount[1] ?? 0));
        $this->assertDatabaseCount('procedures', 0);
    }
}
