<?php

namespace Tests\Unit;

use App\Services\Admin\SvgSanitizer;
use DOMDocument;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SvgSanitizerTest extends TestCase
{
    #[DataProvider('unsafeContent')]
    public function test_removes_unsafe_content(string $content, string $forbidden): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'.$content.'<path d="M0 0h4"/></svg>';
        $clean = (new SvgSanitizer)->sanitize($svg);

        $this->assertStringNotContainsString($forbidden, $clean);
        $this->assertStringContainsString('d="M0 0h4"', $clean);
    }

    public static function unsafeContent(): array
    {
        return [
            'script' => ['<script>alert(1)</script>', '<script'],
            'event' => ['<g onload="alert(1)" ONCLICK="alert(1)"/>', 'alert(1)'],
            'external href' => ['<linearGradient href="https://evil.test/icon"/>', 'href='],
            'xlink href' => ['<linearGradient xlink:href="//evil.test/icon"/>', 'xlink:href='],
            'use' => ['<use href="https://evil.test/icon"/>', '<use'],
            'internal use' => ['<use href="#path"/>', '<use'],
            'javascript' => ['<path fill="jav&#x61;script:alert(1)"/>', 'fill='],
            'whitespace URL' => ['<path fill="java&#10;script:alert(1)"/>', 'fill='],
            'data URL' => ['<path fill="data:image/svg+xml;base64,AAAA"/>', 'fill='],
            'external fill' => ['<path fill="url(https://evil.test/a)"/>', 'fill='],
            'external attribute' => ['<g id="http://evil.test/a"/>', 'id='],
            'style element' => ['<style>path{fill:url(//evil.test/a)}</style>', '<style'],
            'style attribute' => ['<g style="fill:url(#paint)"/>', 'style='],
            'expression' => ['<g style="width:expression(alert(1))"/>', 'style='],
            'css escapes' => ['<path fill="u\72l(//evil.test/a)"/>', 'fill='],
            'foreignObject' => ['<foreignObject><div xmlns="http://www.w3.org/1999/xhtml">bad</div></foreignObject>', 'foreignObject'],
            'iframe' => ['<iframe/>', 'iframe'],
            'object' => ['<object/>', 'object'],
            'embed' => ['<embed/>', 'embed'],
            'foreign namespace' => ['<path xmlns="http://www.w3.org/1999/xhtml" id="bad"/>', 'id="bad"'],
            'namespace reset' => ['<path xmlns="" id="bad"/>', 'id="bad"'],
            'xml base' => ['<g xml:base="https://evil.test/"/>', 'xml:base'],
            'animation' => ['<animate attributeName="href" values="javascript:alert(1)"/>', '<animate'],
            'processing instruction' => ['<?xml-stylesheet href="https://evil.test/a"?>', '<?xml-stylesheet'],
        ];
    }

    #[DataProvider('invalidDocuments')]
    public function test_rejects_invalid_documents(string $svg): void
    {
        $this->expectException(ValidationException::class);
        (new SvgSanitizer)->sanitize($svg);
    }

    public static function invalidDocuments(): array
    {
        return [
            ['<!DOCTYPE svg SYSTEM "file:///etc/passwd"><svg/>'],
            ['<!DOCTYPE svg [<!ENTITY x "boom">]><svg>&x;</svg>'],
            ['<!ENTITY x "boom"><svg/>'],
            ['<html/>'],
            ['<svg xmlns="http://www.w3.org/1999/xhtml"/>'],
            ['<svg><path></svg>'],
            ['<svg><unknown:path/></svg>'],
            [''],
            ["<svg>\0</svg>"],
            ['<svg>'.str_repeat(' ', 51200).'</svg>'],
        ];
    }

    public function test_preserves_clean_geometry_gradients_and_internal_references(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><title>Icon</title><defs><linearGradient id="paint"><stop offset="0" stop-color="#123456"/></linearGradient><linearGradient id="copy" href="#paint"/><clipPath id="clip"><rect width="24" height="24"/></clipPath></defs><g clip-path="url(#clip)"><path d="M0 0h4" fill="url(#paint)"/></g></svg>';
        $expected = new DOMDocument;
        $expected->loadXML($svg);
        $actual = new DOMDocument;
        $actual->loadXML((new SvgSanitizer)->sanitize($svg));

        $this->assertSame($expected->C14N(), $actual->C14N());
    }

    public function test_logo_validation_uses_settings_message_and_logo_field(): void
    {
        foreach (['et', 'en'] as $locale) {
            app()->setLocale($locale);
            foreach (['<svg>', '<svg>'.str_repeat(' ', 200 * 1024).'</svg>'] as $svg) {
                try {
                    (new SvgSanitizer)->sanitize($svg, 200 * 1024, 'logo');
                    $this->fail('Invalid logo was accepted.');
                } catch (ValidationException $exception) {
                    $this->assertSame(['logo' => [__('settings.logo_invalid')]], $exception->errors());
                }
            }
        }
    }

    public function test_preserves_all_seed_icons(): void
    {
        foreach (glob(base_path('public/icons/categories/*.svg')) as $path) {
            $expected = new DOMDocument;
            $expected->loadXML(file_get_contents($path));
            $actual = new DOMDocument;
            $actual->loadXML((new SvgSanitizer)->sanitize(file_get_contents($path)));
            $this->assertSame($expected->C14N(), $actual->C14N(), basename($path));
        }
    }

    public function test_restores_libxml_error_setting(): void
    {
        $previous = libxml_use_internal_errors(false);
        try {
            try {
                (new SvgSanitizer)->sanitize('<svg>');
            } catch (ValidationException) {
                $this->assertFalse(libxml_use_internal_errors());
            }
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    public function test_cdata_is_escaped_for_safe_inline_html_display(): void
    {
        $clean = (new SvgSanitizer)->sanitize('<svg xmlns="http://www.w3.org/2000/svg"><title><![CDATA[<b>Example</b>]]></title></svg>');

        $this->assertStringNotContainsString('<![CDATA[', $clean);
        $this->assertStringContainsString('&lt;b&gt;Example&lt;/b&gt;', $clean);
    }
}
