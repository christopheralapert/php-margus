<?php

namespace Database\Factories;

use App\Enums\TimelinePhase;
use App\Models\Procedure;
use App\Models\TimelineStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TimelineStep> */
class TimelineStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'procedure_id' => Procedure::factory(),
            'phase' => fake()->randomElement(TimelinePhase::cases()),
            'marker' => ['et' => 'Näidisaeg', 'en' => 'Sample time'],
            'title' => ['et' => 'MUSTAND — näidisetapp', 'en' => 'DRAFT — sample step'],
            'body' => ['et' => 'MUSTAND — näidistekst', 'en' => 'DRAFT — sample text'],
            'sort' => 0,
        ];
    }
}
