<?php

namespace App\Services\Leaflet;

use InvalidArgumentException;

final readonly class ThemeColor
{
    private string $color;

    public function __construct(string $base)
    {
        self::channels($base);
        $this->color = strtoupper($base);
    }

    public function base(): string
    {
        return $this->color;
    }

    public function dark(): string
    {
        $channels = self::channels($this->color);
        $dark = $this->color;

        // Mix the original channels towards black in one-percent steps.
        for ($step = 1; self::contrast($dark, '#FFFFFF') < 4.5; $step++) {
            $dark = self::hex(array_map(fn (int $channel): int => (int) round($channel * (1 - $step / 100)), $channels));
        }

        return $dark;
    }

    public function tint(float $amount = 0.12): string
    {
        if (! is_finite($amount) || $amount < 0 || $amount > 1) {
            throw new InvalidArgumentException('Tint amount must be between zero and one.');
        }

        return self::hex(array_map(fn (int $channel): int => (int) round(255 * (1 - $amount) + $channel * $amount), self::channels($this->color)));
    }

    public function on(): string
    {
        return self::contrast($this->color, '#FFFFFF') >= 4.5 ? '#FFFFFF' : '#1F2937';
    }

    public static function contrast(string $a, string $b): float
    {
        $a = self::luminance($a);
        $b = self::luminance($b);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    public function toArray(): array
    {
        return ['base' => $this->base(), 'dark' => $this->dark(), 'tint' => $this->tint(), 'on' => $this->on()];
    }

    private static function channels(string $hex): array
    {
        if (! preg_match('/\A#[0-9a-fA-F]{6}\z/', $hex)) {
            throw new InvalidArgumentException('Colour must be a #RRGGBB hex string.');
        }

        return array_map(hexdec(...), str_split(substr($hex, 1), 2));
    }

    private static function hex(array $channels): string
    {
        return sprintf('#%02X%02X%02X', ...$channels);
    }

    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (int $channel): float {
            $srgb = $channel / 255;

            return $srgb <= 0.04045 ? $srgb / 12.92 : (($srgb + 0.055) / 1.055) ** 2.4;
        }, self::channels($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
