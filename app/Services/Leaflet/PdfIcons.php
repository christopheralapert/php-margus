<?php

namespace App\Services\Leaflet;

use InvalidArgumentException;

final class PdfIcons
{
    public const NAMES = ['check', 'cross', 'warning-triangle', 'phone', 'clock', 'pin', 'mail', 'calendar', 'pill', 'cup', 'droplet', 'run', 'moon', 'stethoscope', 'info'];

    public function svg(string $name, string $color): string
    {
        if (! in_array($name, self::NAMES, true)) {
            throw new InvalidArgumentException('Unknown PDF icon: '.$name);
        }

        $color = (new ThemeColor($color))->base();

        return str_replace('currentColor', $color, file_get_contents(resource_path('pdf/icons/'.$name.'.svg')));
    }

    public function image(string $name, string $color, float $size = 6): string
    {
        if (! is_finite($size) || $size <= 0 || $size > 24) {
            throw new InvalidArgumentException('Icon size must be between zero and 24 mm.');
        }

        return '<img src="data:image/svg+xml;base64,'.base64_encode($this->svg($name, $color)).'" width="'.$size.'mm" height="'.$size.'mm" alt="">';
    }

    public function dot(string $color, float $size = 3.5): string
    {
        $color = (new ThemeColor($color))->base();
        if (! is_finite($size) || $size <= 0 || $size > 24) {
            throw new InvalidArgumentException('Dot size must be between zero and 24 mm.');
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="'.$color.'"/></svg>';

        return '<img src="data:image/svg+xml;base64,'.base64_encode($svg).'" width="'.$size.'mm" height="'.$size.'mm" alt="">';
    }
}
