<?php

namespace Tests\Unit;

use App\Services\Leaflet\ThemeColor;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThemeColorTest extends TestCase
{
    public function test_wcag_contrast_reference_values(): void
    {
        $this->assertEqualsWithDelta(21, ThemeColor::contrast('#000000', '#FFFFFF'), 0.00001);
        $this->assertEqualsWithDelta(1, ThemeColor::contrast('#FFFFFF', '#FFFFFF'), 0.00001);
        $this->assertEqualsWithDelta(4.478, ThemeColor::contrast('#777777', '#FFFFFF'), 0.001);
        $this->assertSame(ThemeColor::contrast('#123456', '#ABCDEF'), ThemeColor::contrast('#ABCDEF', '#123456'));
    }

    public function test_derived_colours_and_uppercase_output(): void
    {
        $color = new ThemeColor('#2e6fd8');

        $this->assertSame('#2E6FD8', $color->base());
        $this->assertSame($color->base(), $color->dark());
        $this->assertSame('#FFFFFF', $color->on());
        $this->assertSame('#1F2937', (new ThemeColor('#FFFFFF'))->on());
        $this->assertSame('#E6EEFA', $color->tint());
        $this->assertLessThan(ThemeColor::contrast($color->base(), '#FFFFFF'), ThemeColor::contrast($color->tint(), '#FFFFFF'));
        $this->assertSame('#FFFFFF', $color->tint(0));
        $this->assertSame($color->base(), $color->tint(1));
        $this->assertSame([
            'base' => $color->base(), 'dark' => $color->dark(), 'tint' => $color->tint(), 'on' => $color->on(),
        ], $color->toArray());
        $this->assertSame('#1F2937', (new ThemeColor('#2BB3A3'))->on());
    }

    #[DataProvider('invalidHexes')]
    public function test_invalid_hex_is_rejected(string $hex): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ThemeColor($hex);
    }

    #[DataProvider('invalidHexes')]
    public function test_contrast_also_validates_its_inputs(string $hex): void
    {
        $this->expectException(InvalidArgumentException::class);
        ThemeColor::contrast('#FFFFFF', $hex);
    }

    public static function invalidHexes(): array
    {
        return array_map(fn (string $hex): array => [$hex], ['', '#FFF', '123456', '#GG0000', '#12345678', "#123456\n"]);
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_tint_amount_is_rejected(float $amount): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ThemeColor('#123456'))->tint($amount);
    }

    public static function invalidAmounts(): array
    {
        return [[-0.1], [1.1], [INF], [NAN]];
    }
}
