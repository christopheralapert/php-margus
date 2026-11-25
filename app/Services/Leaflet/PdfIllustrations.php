<?php

namespace App\Services\Leaflet;

use DOMDocument;
use DOMElement;

final class PdfIllustrations
{
    public function circleDataUri(string $path, ThemeColor $theme): string
    {
        $source = new DOMDocument;
        $source->loadXML((new PdfAssetLoader)->load(public_path($path)), LIBXML_NONET);
        $svg = new DOMDocument;
        $svg->loadXML('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160"><circle cx="80" cy="80" r="80" fill="'.$theme->tint(0.06).'"/><g transform="translate(0 20)"/></svg>');
        $group = $svg->getElementsByTagName('g')->item(0);

        foreach ($source->documentElement->childNodes as $node) {
            // The original set starts with a soft rectangular backdrop; replace
            // that with the circle, keeping the actual drawing at its native size.
            if ($node instanceof DOMElement && $node === $source->documentElement->firstElementChild && $node->localName === 'rect'
                && $node->getAttribute('x') === '15' && $node->getAttribute('y') === '12'
                && $node->getAttribute('width') === '130' && $node->getAttribute('height') === '96') {
                continue;
            }
            $group->appendChild($svg->importNode($node, true));
        }

        return 'data:image/svg+xml;base64,'.base64_encode($svg->saveXML($svg->documentElement));
    }
}
