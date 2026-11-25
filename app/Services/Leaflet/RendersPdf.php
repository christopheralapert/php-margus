<?php

namespace App\Services\Leaflet;

interface RendersPdf
{
    public function render(LeafletDocument $doc, bool $printMode = false, bool $categoryProof = false): string;
}
