<?php

namespace App\Services\Leaflet;

use InvalidArgumentException;

final class PdfIcons
{
    private array $sources = [];

    public const NAMES = ['check', 'cross', 'warning-triangle', 'phone', 'clock', 'pin', 'mail', 'calendar', 'pill', 'cup', 'droplet', 'run', 'moon', 'stethoscope', 'info'];

    public function category(string $slug, string $color): string
    {
        if (! preg_match('/\A[a-z]+(?:-[a-z]+)*\z/', $slug) || ! is_file(public_path('icons/categories/'.$slug.'.svg'))) {
            return '';
        }
        $color = (new ThemeColor($color))->base();
        $path = public_path('icons/categories/'.$slug.'.svg');
        $source = str_replace('currentColor', $color, $this->sources[$path] ??= file_get_contents($path));
        // mPDF does not preserve nested SVG viewports. Move the source root's
        // presentation attributes to a group in the disc's coordinate system.
        $source = preg_replace('/<svg\b[^>]*>/', '<g transform="translate(12 12)" fill="none" stroke="'.$color.'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">', $source);
        $source = str_replace('</svg>', '</g>', $source);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><circle cx="24" cy="24" r="24" fill="#FFFFFF"/>'.$source.'</svg>';

        return '<img src="data:image/svg+xml;base64,'.base64_encode($svg).'" width="12mm" height="12mm" alt="">';
    }

    public function timelineRuler(ThemeColor $theme): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 6"><rect x="15" y="2.6" width="90" height="0.8" fill="'.$theme->tint().'"/>';
        foreach (range(1, 4) as $index => $number) {
            $x = 15 + 30 * $index;
            $svg .= $this->numberedCircle($number, $theme, $x);
        }
        $svg .= '</svg>';

        return '<img src="data:image/svg+xml;base64,'.base64_encode($svg).'" width="120mm" height="6mm" alt="">';
    }

    public function timelineCircle(int $number, ThemeColor $theme): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 6 6">'.$this->numberedCircle($number, $theme, 3).'</svg>';

        return '<img src="data:image/svg+xml;base64,'.base64_encode($svg).'" width="6mm" height="6mm" alt="">';
    }

    private function numberedCircle(int $number, ThemeColor $theme, int $x): string
    {
        $digits = [
            'M-0.8 -0.7 L0 -1.4 L0 1.4 M-0.8 1.4 H0.8',
            'M-0.85 -0.85 C-0.8 -1.7 0.9 -1.7 0.9 -0.7 C0.9 -0.1 -0.9 0.6 -0.9 1.4 H1',
            'M-0.9 -1.1 C0.9 -2 1.5 -0.1 0 -0.05 C1.6 -0.1 1.1 2 -0.9 1.1',
            'M0.55 1.4 V-1.4 L-1 0.5 H1.1',
        ];
        if ($number < 1 || $number > count($digits)) {
            throw new InvalidArgumentException('Timeline number must be between 1 and 4.');
        }

        return '<circle cx="'.$x.'" cy="3" r="3" fill="'.$theme->base().'"/><path d="'.$digits[$number - 1].'" transform="translate('.$x.' 3)" fill="none" stroke="#FFFFFF" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round"/>';
    }

    public function svg(string $name, string $color): string
    {
        if (! in_array($name, self::NAMES, true)) {
            throw new InvalidArgumentException('Unknown PDF icon: '.$name);
        }

        $color = (new ThemeColor($color))->base();

        $path = resource_path('pdf/icons/'.$name.'.svg');

        return str_replace('currentColor', $color, $this->sources[$path] ??= file_get_contents($path));
    }

    public function image(string $name, string $color, float $size = 6): string
    {
        if (! is_finite($size) || $size <= 0 || $size > 24) {
            throw new InvalidArgumentException('Icon size must be between zero and 24 mm.');
        }

        return '<img src="data:image/svg+xml;base64,'.base64_encode($this->svg($name, $color)).'" width="'.$size.'mm" height="'.$size.'mm" alt="">';
    }

    public function badge(string $name, string $color): string
    {
        $color = (new ThemeColor($color))->base();
        $mark = preg_replace('/<\/?svg\b[^>]*>/', '', $this->svg($name, '#FFFFFF'));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="16" cy="16" r="16" fill="'.$color.'"/><g transform="translate(4 4)">'.$mark.'</g></svg>';

        return '<img src="data:image/svg+xml;base64,'.base64_encode($svg).'" width="6mm" height="6mm" alt="">';
    }

    public function tileCap(bool $bottom, float $width): string
    {
        // mPDF ignores block backgrounds/radii inside table cells. These purely
        // decorative caps preserve 3mm corners without putting radii on cells.
        $right = $width - 3;
        $path = $bottom
            ? 'M0 0H'.$width.'A3 3 0 0 1 '.$right.' 3H3A3 3 0 0 1 0 0Z'
            : 'M0 3A3 3 0 0 1 3 0H'.$right.'A3 3 0 0 1 '.$width.' 3Z';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' 3"><path d="'.$path.'" fill="#F3F7FC"/></svg>';

        return '<img src="data:image/svg+xml;base64,'.base64_encode($svg).'" width="'.$width.'mm" height="3mm" alt="">';
    }

    public function emergencyCap(bool $bottom): string
    {
        // Only the rounded decoration is SVG; the label and number stay text.
        $path = $bottom ? 'M0 0H32A4 4 0 0 1 28 4H4A4 4 0 0 1 0 0Z' : 'M0 4A4 4 0 0 1 4 0H28A4 4 0 0 1 32 4Z';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 4"><path d="'.$path.'" fill="#D93B3B"/></svg>';

        return '<img src="data:image/svg+xml;base64,'.base64_encode($svg).'" width="32mm" height="4mm" alt="">';
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
