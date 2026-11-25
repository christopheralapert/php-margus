<?php

namespace Tests\Security;

use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class InjectionAndBoundsTest extends SecurityTestCase
{
    public function test_admin_search_is_bound_and_filter_injection_is_rejected(): void
    {
        $procedure = Procedure::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        DB::enableQueryLog();
        $needle = "%' OR 1=1 --";
        $this->get('/en/admin/procedures?'.http_build_query(['search' => $needle]))->assertOk()
            ->assertViewHas('procedures', fn ($procedures) => $procedures->total() === 0);
        $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'json_extract'));
        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertStringNotContainsString($needle, $query['query']);
            $this->assertContains("%\\%' OR 1=1 --%", $query['bindings']);
        }
        foreach (['status', 'category_id'] as $filter) {
            $this->getJson('/en/admin/procedures?'.http_build_query([$filter => '1 OR 1=1']))->assertUnprocessable();
        }
        $this->assertModelExists($procedure);
    }

    public function test_combo_and_procedure_id_queries_do_not_interpret_sql(): void
    {
        $procedure = Procedure::factory()->create();
        $needle = "x' OR 1=1 --";
        DB::enableQueryLog();
        $this->get('/en/l/'.rawurlencode($needle))->assertNotFound();
        $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"slug" in'));
        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertStringNotContainsString($needle, $query['query']);
            $this->assertContains($needle, $query['bindings']);
        }
        $this->postJson('/en/leaflet', ['procedures' => [$procedure->id.' OR 1=1']])->assertUnprocessable();
        $this->assertModelExists($procedure);
    }

    public function test_procedure_array_and_combo_caps_reject_without_catalogue_queries(): void
    {
        $ids = Procedure::factory()->count(6)->create()->modelKeys();
        $this->postJson('/en/leaflet', ['procedures' => $ids])->assertUnprocessable()->assertJsonValidationErrors('procedures');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get('/en/l/'.implode('+', array_fill(0, 6, 'unknown')))->assertNotFound();
        $this->assertCount(0, collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'from "procedures"')));
        $this->assertSame(0, $this->llm->calls);
    }
}
