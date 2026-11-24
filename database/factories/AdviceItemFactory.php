<?php

namespace Database\Factories;

use App\Enums\AdviceSection;
use App\Models\AdviceItem;
use App\Models\Procedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdviceItem> */
class AdviceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'procedure_id' => Procedure::factory(),
            'section' => fake()->randomElement(AdviceSection::cases()),
            'text' => ['et' => 'MUSTAND — näidisnõuanne', 'en' => 'DRAFT — sample advice'],
            'merge_key' => null,
            'sort' => 0,
        ];
    }
}
