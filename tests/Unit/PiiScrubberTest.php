<?php

namespace Tests\Unit;

use App\Services\Chat\PiiScrubber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PiiScrubberTest extends TestCase
{
    #[DataProvider('identifiers')]
    public function test_identifiers_are_masked(string $input, string $expected): void
    {
        $this->assertSame($expected, (new PiiScrubber)->scrub($input, 'Mari'));
    }

    public static function identifiers(): array
    {
        return [
            ['mari@example.com', '[email]'],
            ['mari+care@example.co.uk', '[email]'],
            ['+372 5123 4567', '[phone]'],
            ['+44 (20) 7946 0958', '[phone]'],
            ['202-555-0123', '[phone]'],
            ['00372 51234567', '[phone]'],
            ['51234', '[phone]'],
            ['512 3456', '[phone]'],
            ['61234567', '[phone]'],
            ['48912310000', '[id]'],
            ['12345678901', '[id]'],
            ['31.12.1989', '[date]'],
            ['1989-12-31', '[date]'],
            ['1/2/1989', '[date]'],
            ['MARI ja Mari, marigold', '[name] ja [name], marigold'],
            ['38 °C and 2 days', '38 °C and 2 days'],
        ];
    }
}
