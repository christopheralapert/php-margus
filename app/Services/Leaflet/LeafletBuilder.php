<?php

namespace App\Services\Leaflet;

use App\Models\Procedure;
use Carbon\CarbonImmutable;

final class LeafletBuilder
{
    public function build(array $procedureIds, string $locale, ?string $firstName, ?string $date): LeafletDocument
    {
        $procedures = Procedure::active()->with(['category', 'timelineSteps', 'adviceItems'])
            ->whereIn('id', $procedureIds)->get()->keyBy('id');
        $sections = [];

        foreach (array_unique($procedureIds) as $id) {
            if ($procedure = $procedures->get($id)) {
                $sections[] = ['procedure' => $procedure, 'theme' => new ThemeColor($procedure->themeColor())];
            }
        }

        return new LeafletDocument($locale, $firstName, $date ? CarbonImmutable::createFromFormat('!Y-m-d', $date) : null, $sections);
    }
}
