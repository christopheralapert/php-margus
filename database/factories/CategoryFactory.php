<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => ['et' => 'Näidiskategooria', 'en' => 'Sample category'],
            'icon_path' => 'illustrations/sample.svg',
            'color' => fake()->hexColor(),
            'sort' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
