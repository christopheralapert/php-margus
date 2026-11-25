<?php

namespace Database\Factories;

use App\Enums\LeafletFormat;
use App\Models\Procedure;
use App\Models\SavedLeaflet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SavedLeaflet> */
class SavedLeafletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'locale' => 'et',
            'procedure_ids' => fn () => [Procedure::factory()->create()->id],
            'first_name' => fake()->firstName(),
            'procedure_date' => fake()->date(),
            'format' => LeafletFormat::A5,
            'last_downloaded_at' => null,
        ];
    }
}
