<?php

namespace App\Services\Leaflet;

final readonly class OnlineLeafletUrl
{
    public function __construct(private LeafletDocument $document) {}

    public function onlineUrl(): string
    {
        return route('online.show', [
            'locale' => $this->document->locale,
            'combo' => implode('+', array_map(fn (array $section): string => $section['procedure']->slug, $this->document->sections)),
        ]);
    }
}
