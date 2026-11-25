<?php

namespace Tests\Feature;

use App\Services\Leaflet\PdfIcons;
use App\Services\Leaflet\PdfIllustrations;
use App\Services\Leaflet\PdfRenderer;
use App\Services\Leaflet\ThemeColor;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PdfIconsTest extends TestCase
{
    public function test_every_icon_is_original_flat_self_contained_svg(): void
    {
        $files = glob(resource_path('pdf/icons/*.svg'));
        $names = array_map(fn (string $path): string => basename($path, '.svg'), $files);
        $this->assertEqualsCanonicalizing(PdfIcons::NAMES, $names);
        $drawings = [];
        foreach ($files as $file) {
            $svg = new DOMDocument;
            $this->assertTrue($svg->load($file, LIBXML_NONET));
            $this->assertNull($svg->doctype);
            $this->assertSame('svg', $svg->documentElement->localName);
            $this->assertSame('0 0 24 24', $svg->documentElement->getAttribute('viewBox'));
            foreach ((new DOMXPath($svg))->query('//*') as $element) {
                $this->assertContains($element->localName, ['svg', 'path', 'circle', 'rect', 'polygon']);
                $this->assertSame('currentColor', $element->getAttribute('fill'));
                foreach ($element->attributes as $attribute) {
                    $this->assertContains($attribute->nodeName, ['xmlns', 'viewBox', 'fill', 'fill-rule', 'd', 'x', 'y', 'cx', 'cy', 'r', 'rx', 'width', 'height', 'points']);
                    if ($attribute->nodeName !== 'xmlns') {
                        $this->assertDoesNotMatchRegularExpression('/https?:|url\s*\(/i', $attribute->value);
                    }
                }
            }
            $drawings[] = hash_file('sha256', $file);
        }
        $this->assertCount(15, array_unique($drawings));
    }

    public function test_helper_replaces_every_fill_and_sizes_the_image_in_mm(): void
    {
        $icons = app(PdfIcons::class);
        foreach (PdfIcons::NAMES as $name) {
            $html = $icons->image($name, '#7c5cd6', 4.5);
            $this->assertStringContainsString('width="4.5mm" height="4.5mm"', $html);
            preg_match('/base64,([^"]+)/', $html, $matches);
            $svg = base64_decode($matches[1], true);
            $this->assertStringNotContainsString('currentColor', $svg);
            $this->assertStringContainsString('fill="#7C5CD6"', $svg);
        }
        $this->assertStringContainsString('width="3.5mm"', $icons->dot('#2E6FD8'));
    }

    public static function invalidIcons(): array
    {
        return [
            ['../check', '#123456', 6],
            ['unknown', '#123456', 6],
            ['check', 'red', 6],
            ['check', '#123456" onload="x', 6],
            ['check', '#123456', 0],
            ['check', '#123456', -1],
            ['check', '#123456', INF],
            ['check', '#123456', 25],
        ];
    }

    #[DataProvider('invalidIcons')]
    public function test_helper_rejects_invalid_names_colors_and_sizes(string $name, string $color, float $size): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(PdfIcons::class)->image($name, $color, $size);
    }

    public function test_circle_thumbnail_preserves_art_and_uses_the_procedure_tint(): void
    {
        $uri = app(PdfIllustrations::class)->circleDataUri('illustrations/dental/tooth-extraction.svg', new ThemeColor('#2E6FD8'));
        $svg = new DOMDocument;
        $this->assertTrue($svg->loadXML(base64_decode(explode(',', $uri, 2)[1]), LIBXML_NONET));
        $this->assertSame('0 0 160 160', $svg->documentElement->getAttribute('viewBox'));
        $this->assertSame((new ThemeColor('#2E6FD8'))->tint(0.06), $svg->getElementsByTagName('circle')->item(0)->getAttribute('fill'));
        $this->assertSame(0, $svg->getElementsByTagName('rect')->length);
        $this->assertGreaterThan(0, $svg->getElementsByTagName('path')->length);
    }

    public function test_icon_sheet_renders_as_a_single_page(): void
    {
        $pdf = app(PdfRenderer::class)->renderIconSheet();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('/Count 1', $pdf);
    }
}
