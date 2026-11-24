<?php

namespace App\Services\Leaflet;

use App\Enums\LeafletFormat;
use App\Models\SavedLeaflet;
use App\Models\User;

final class SaveAccountLeaflet
{
    public function save(User $user, array $wizard, string $locale): ?SavedLeaflet
    {
        if (empty($wizard['procedures'])) {
            return null;
        }

        return $user->savedLeaflets()->create([
            'locale' => $locale,
            'procedure_ids' => array_values($wizard['procedures']),
            'first_name' => ($wizard['consent'] ?? false) === true ? ($wizard['first_name'] ?? null) : null,
            'procedure_date' => ($wizard['consent'] ?? false) === true ? ($wizard['date'] ?? null) : null,
            'format' => LeafletFormat::A5,
        ]);
    }
}
