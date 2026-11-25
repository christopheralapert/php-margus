<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class DateFormat
{
    public static function date(?DateTimeInterface $date, ?string $locale = null): ?string
    {
        return $date?->format(($locale ?? app()->getLocale()) === 'et' ? 'd.m.Y' : 'j M Y');
    }

    public static function dateTime(?DateTimeInterface $date, ?string $locale = null): ?string
    {
        return $date === null ? null : self::date($date, $locale).' '.$date->format('H:i');
    }

    public static function input(?DateTimeInterface $date, ?string $locale = null): ?string
    {
        return $date?->format(($locale ?? app()->getLocale()) === 'et' ? 'd.m.Y' : 'd/m/Y');
    }

    public static function parseInput(mixed $value, ?string $locale = null): ?CarbonImmutable
    {
        $separators = ($locale ?? app()->getLocale()) === 'et' ? '[.\/-]' : '[.\/]';
        if (! is_string($value) || ! preg_match('/\A([0-9]{1,2})('.$separators.')([0-9]{1,2})\2([0-9]{4})\z/', $value, $parts)) {
            return null;
        }

        $day = (int) $parts[1];
        $month = (int) $parts[3];
        $year = (int) $parts[4];
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!j.n.Y', $day.'.'.$month.'.'.$year);
    }
}
