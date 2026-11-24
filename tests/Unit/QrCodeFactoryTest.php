<?php

namespace Tests\Unit;

use App\Services\Leaflet\QrCodeFactory;
use PHPUnit\Framework\TestCase;

class QrCodeFactoryTest extends TestCase
{
    public function test_png_is_deterministic_square_and_print_resolution_with_ink_on_white(): void
    {
        $factory = new QrCodeFactory;
        $url = '/et/l/tooth-extraction+mole-removal';
        $uri = $factory->dataUri($url);
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $this->assertSame($uri, $factory->dataUri($url));
        $png = base64_decode(substr($uri, strlen('data:image/png;base64,')), true);
        [$width, $height, $type] = getimagesizefromstring($png);
        $this->assertSame(IMAGETYPE_PNG, $type);
        $this->assertSame($width, $height);
        // Enlarge rounds up to whole QR modules, preserving sharp edges.
        $this->assertGreaterThanOrEqual(400, $width);
        $this->assertLessThan(450, $width);
        $image = imagecreatefromstring($png);
        $this->assertSame(['red' => 31, 'green' => 41, 'blue' => 55, 'alpha' => 0], imagecolorsforindex($image, imagecolorat($image, 0, 0)));
        $colors = [];
        for ($x = 0; $x < $width; $x++) {
            $colors[] = imagecolorsforindex($image, imagecolorat($image, $x, 0));
        }
        $this->assertContains(['red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0], $colors);
        imagedestroy($image);
    }
}
