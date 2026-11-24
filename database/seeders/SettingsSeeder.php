<?php

namespace Database\Seeders;

use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(Settings $settings): void
    {
        foreach (config('clinic') as $group => $values) {
            foreach ($values as $key => $value) {
                $settings->set($group.'.'.$key, $value);
            }
        }
    }
}
