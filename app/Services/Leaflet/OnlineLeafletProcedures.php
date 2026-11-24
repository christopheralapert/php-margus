<?php

namespace App\Services\Leaflet;

use App\Models\Procedure;
use Illuminate\Support\Collection;

final class OnlineLeafletProcedures
{
    /** @return Collection<int, Procedure> */
    public function resolve(string $combo): Collection
    {
        $slugs = explode('+', $combo);
        abort_if(count($slugs) > 5 || in_array('', $slugs, true), 404);
        $procedures = Procedure::active()->whereIn('slug', $slugs)->get()->keyBy('slug');
        abort_unless($procedures->count() === count(array_unique($slugs)), 404);

        return collect($slugs)->map(fn (string $slug): Procedure => $procedures->get($slug));
    }
}
