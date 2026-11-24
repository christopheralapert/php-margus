<?php

namespace Database\Factories;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use App\Enums\TimelinePhase;
use App\Models\AdviceItem;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\TimelineStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Procedure> */
class ProcedureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'slug' => fake()->unique()->slug(),
            'name' => ['et' => 'Näidisprotseduur', 'en' => 'Sample procedure'],
            'summary' => ['et' => 'MUSTAND — näidiskokkuvõte', 'en' => 'DRAFT — sample summary'],
            'color' => null,
            'illustration_path' => 'illustrations/sample.svg',
            'cover_illustration_path' => null,
            'status' => ProcedureStatus::Draft,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'search_keywords' => ['et' => 'näidis', 'en' => 'sample'],
            'follow_up' => ['et' => 'MUSTAND — näidisjärelkontroll', 'en' => 'DRAFT — sample follow-up'],
            'sort' => 0,
            'is_active' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ProcedureStatus::Draft]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ProcedureStatus::Published]);
    }

    public function withContent(): static
    {
        return $this->afterCreating(function (Procedure $procedure): void {
            foreach (TimelinePhase::cases() as $phase) {
                TimelineStep::factory()->for($procedure)->create(['phase' => $phase]);
            }

            foreach (AdviceSection::cases() as $section) {
                AdviceItem::factory()->for($procedure)->count(3)
                    ->sequence(['sort' => 0], ['sort' => 1], ['sort' => 2])
                    ->create(['section' => $section]);
            }
        });
    }
}
