<?php

namespace App\Services\Leaflet;

use App\Enums\LeafletFormat;

interface RendersPdf
{
    public function render(LeafletDocument $doc, bool $printMode = false, bool $categoryProof = false, LeafletFormat $format = LeafletFormat::A5): string;
}
