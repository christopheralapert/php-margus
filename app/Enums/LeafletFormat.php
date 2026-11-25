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

    public function filename(string $locale, ?string $stem = null): string
    {
        if ($stem !== null) {
            return $stem.($this === self::Trifold ? '-'.__('wizard.trifold_suffix', [], $locale) : '').'.pdf';
        }

        return __($this === self::Trifold ? 'wizard.trifold_filename' : 'wizard.pdf_filename', [], $locale);
    }
}
