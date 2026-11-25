<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['dental', 'Hambaravi', 'Dental', '#2E6FD8'],
            ['dermatology', 'Dermatoloogia', 'Dermatology', '#E2725B'],
            ['aesthetic', 'Esteetiline ravi', 'Aesthetic & cosmetic', '#D9488F'],
            ['eye', 'Silmaravi', 'Eye', '#2BB3A3'],
            ['ent', 'Kõrva-nina-kurguhaigused', 'ENT', '#7C5CD6'],
            ['surgery', 'Üldkirurgia ja väikekirurgia', 'General & minor surgery', '#3B8C5A'],
            ['orthopaedics', 'Ortopeedia ja füsioteraapia', 'Orthopaedics & physio', '#F08A24'],
            ['gynaecology', 'Günekoloogia', 'Gynaecology', '#A23E9C'],
            ['vaccination', 'Vaktsineerimine ja süstid', 'Vaccination & injections', '#0EA5C6'],
        ];

        foreach ($categories as $sort => [$slug, $et, $en, $color]) {
            Category::updateOrCreate(['slug' => $slug], [
                'name' => ['et' => $et, 'en' => $en],
                'icon_path' => 'icons/categories/'.$slug.'.svg',
                'color' => $color,
                'sort' => $sort,
                'is_active' => true,
            ]);
        }
    }
}
