<?php

namespace App\Enums;

enum LeafletFormat: string
{
    case A5 = 'a5';
    case Trifold = 'trifold';

    public function label(): string
    {
        return __('enums.leaflet_format.'.$this->value);
    }
}
