<?php

namespace Tests\Unit;

use App\Models\Procedure;
use App\Services\Leaflet\LeafletBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeafletBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_active_filter_and_eager_loading_have_a_constant_query_budget(): void
    {
        $procedures = Procedure::factory()->count(3)->withContent()->create();
        $inactive = Procedure::factory()->create(['is_active' => false]);
        $ids = array_values($procedures->reverse()->modelKeys());
        DB::enableQueryLog();
        DB::flushQueryLog();
        $doc = app(LeafletBuilder::class)->build([...$ids, $inactive->id, 999999], 'en', 'Mari', '2026-11-24');
        foreach ($doc->sections as $section) {
            $this->assertTrue($section['procedure']->relationLoaded('category'));
            $this->assertTrue($section['procedure']->relationLoaded('timelineSteps'));
            $this->assertTrue($section['procedure']->relationLoaded('adviceItems'));
            $section['procedure']->category->name;
            $section['procedure']->timelineSteps->all();
            $section['procedure']->adviceItems->all();
        }
        $this->assertLessThanOrEqual(4, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $this->assertSame($ids, array_map(fn ($section) => $section['procedure']->id, $doc->sections));
        $this->assertSame([], $doc->general);
        $this->assertTrue($doc->hasDraft());
        $this->assertSame('24 Nov 2026', $doc->formattedDate());
        $this->assertSame(__('wizard.leaflet_title', [], 'en'), $doc->title());
    }

    public function test_empty_and_published_documents_have_no_draft(): void
    {
        $builder = app(LeafletBuilder::class);
        $this->assertFalse($builder->build([], 'et', null, null)->hasDraft());
        $procedure = Procedure::factory()->published()->create();
        $this->assertFalse($builder->build([$procedure->id], 'et', null, null)->hasDraft());
    }
}
