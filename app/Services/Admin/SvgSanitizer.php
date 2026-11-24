<?php

namespace App\Services\Admin;

use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;

final class SvgSanitizer
{
    private const SVG_NS = 'http://www.w3.org/2000/svg';

    private const ELEMENTS = ['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'title', 'desc', 'defs', 'linearGradient', 'radialGradient', 'stop', 'clipPath', 'mask'];

    private const ATTRIBUTES = ['id', 'viewBox', 'width', 'height', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points', 'fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'opacity', 'transform', 'preserveAspectRatio', 'clip-path', 'clip-rule', 'mask', 'maskUnits', 'maskContentUnits', 'clipPathUnits', 'gradientUnits', 'gradientTransform', 'spreadMethod', 'offset', 'stop-color', 'stop-opacity', 'fx', 'fy', 'fr', 'href', 'color', 'vector-effect'];

    public function sanitize(string $svg): string
    {
        // Reject alternate encodings too: NUL bytes can hide declarations from a byte scan.
        if ($svg === '' || strlen($svg) > 50 * 1024 || str_contains($svg, "\0") || preg_match('/<!\s*(DOCTYPE|ENTITY)/i', $svg)) {
            throw ValidationException::withMessages(['icon' => __('admin.svg.invalid')]);
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;
            $loaded = $document->loadXML($svg, LIBXML_NONET);
            $root = $document->documentElement;
            $hasErrors = collect(libxml_get_errors())->contains(fn ($error) => $error->level >= LIBXML_ERR_ERROR);

            if (! $loaded || $hasErrors || ! $root || $document->doctype || $root->localName !== 'svg' || ! in_array($root->namespaceURI, [null, self::SVG_NS], true)) {
                throw ValidationException::withMessages(['icon' => __('admin.svg.invalid')]);
            }

            $this->clean($root, $root->namespaceURI);

            // Serialize only the root, excluding processing instructions outside it.
            return $document->saveXML($root);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function clean(DOMElement $element, ?string $namespace): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $value = preg_replace('/[\x00-\x20\x7f]+/', '', $attribute->value);
            $name = $attribute->localName;
            $allowedNamespace = $attribute->namespaceURI === null
                || ($attribute->namespaceURI === 'http://www.w3.org/1999/xlink' && $name === 'href');

            if (! $allowedNamespace || ! in_array($name, self::ATTRIBUTES, true)
                || preg_match('~(?:javascript:|data:|https?:|//|expression\(|\\\\)~i', $value)
                || ($name === 'href' && ! preg_match('/^#[a-zA-Z_][\w:.-]*$/D', $value))
                || (stripos($value, 'url(') !== false && ! preg_match('/^url\(#[a-zA-Z_][\w:.-]*\)$/D', $value))) {
                $element->removeAttributeNode($attribute);
            }
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                if ($child->namespaceURI !== $namespace || ! in_array($child->localName, self::ELEMENTS, true)) {
                    $element->removeChild($child);
                } else {
                    $this->clean($child, $namespace);
                }
            } elseif ($child->nodeType === XML_CDATA_SECTION_NODE) {
                // HTML parses SVG title/desc as integration points; escape CDATA as text.
                $element->replaceChild($element->ownerDocument->createTextNode($child->textContent), $child);
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                $element->removeChild($child);
            }
        }
    }
}
