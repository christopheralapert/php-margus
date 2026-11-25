<?php

namespace Tests\Unit;

use App\Support\DateFormat;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DateFormatTest extends TestCase
{
    public static function locales(): array
    {
        return [
            ['et', '04.02.2026', '04.02.2026 14:05', '04.02.2026'],
            ['en', '4 Feb 2026', '4 Feb 2026 14:05', '04/02/2026'],
        ];
    }

    #[DataProvider('locales')]
    public function test_formats_dates_and_times_without_mutating_the_date(string $locale, string $date, string $dateTime, string $input): void
    {
        app()->setLocale($locale);
        foreach ([new DateTimeImmutable('2026-02-04 14:05:00'), Carbon::parse('2026-02-04 14:05:00')->locale('et'), CarbonImmutable::parse('2026-02-04 14:05:00')] as $value) {
            $before = serialize($value);
            $this->assertSame($date, DateFormat::date($value));
            $this->assertSame($dateTime, DateFormat::dateTime($value));
            $this->assertSame($input, DateFormat::input($value));
            $this->assertSame($before, serialize($value));
        }
    }

    public function test_explicit_locale_overrides_the_page_locale_and_null_dates_stay_null(): void
    {
        app()->setLocale('et');
        $date = CarbonImmutable::parse('2026-11-24 14:05');
        $this->assertSame('24 Nov 2026', DateFormat::date($date, 'en'));
        $this->assertSame('24 Nov 2026 14:05', DateFormat::dateTime($date, 'en'));
        $this->assertSame('24/11/2026', DateFormat::input($date, 'en'));
        $this->assertNull(DateFormat::date(null));
        $this->assertNull(DateFormat::dateTime(null));
        $this->assertNull(DateFormat::input(null));
    }

    public function test_parser_returns_an_immutable_date_at_midnight_and_checks_leap_years(): void
    {
        $date = DateFormat::parseInput('29.2.2024', 'et');
        $this->assertInstanceOf(CarbonImmutable::class, $date);
        $this->assertSame('2024-02-29 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertNull(DateFormat::parseInput('29.2.2026', 'et'));
        $this->assertNull(DateFormat::parseInput('24-11-2026', 'en'));
        $this->assertNull(DateFormat::parseInput('24/11.2026', 'et'));
    }
}
