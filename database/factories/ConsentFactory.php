<?php

namespace Database\Factories;

use App\Enums\ConsentPurpose;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Consent> */
class ConsentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => ConsentPurpose::PersonalisedLeaflet,
            'text_version' => '2026-11-v1',
            'locale' => 'et',
            'given_at' => now(),
            'withdrawn_at' => null,
        ];
    }
}
