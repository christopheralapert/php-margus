<?php

namespace App\Services\Leaflet;

use App\Services\Admin\SvgSanitizer;
use Mpdf\File\LocalContentLoaderInterface;
use RuntimeException;

final class PdfAssetLoader implements LocalContentLoaderInterface
{
    public function load($path): string
    {
        $resolved = realpath($path);
        if ($resolved !== false && is_file($resolved)) {
            // mPDF materializes inline SVG in its private cache before reading it.
            $temporary = realpath(config('leaflet.pdf.tempDir').'/mpdf');
            if ($temporary !== false && dirname($resolved) === $temporary
                && preg_match('/^_tempSVG[0-9a-f.]+_(?:[0-9]+|meter|progress)\.svg$/D', basename($resolved))) {
                return app(SvgSanitizer::class)->sanitize(file_get_contents($resolved), 200 * 1024, 'illustration');
            }
            foreach (['brand', 'icons/categories', 'illustrations'] as $directory) {
                $root = realpath(public_path($directory));
                $public = str_replace('\\', '/', realpath(public_path())).'/';
                if ($root !== false && str_starts_with(str_replace('\\', '/', $root).'/', $public)
                    && str_starts_with(str_replace('\\', '/', $resolved), str_replace('\\', '/', $root).'/')) {
                    $extension = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
                    if ($extension === 'svg') {
                        return app(SvgSanitizer::class)->sanitize(file_get_contents($resolved), 200 * 1024, 'illustration');
                    }
                    $size = $extension === 'png' ? @getimagesize($resolved) : false;
                    if ($size && $size[2] === IMAGETYPE_PNG) {
                        return file_get_contents($resolved);
                    }
                }
            }
        }

        throw new RuntimeException('PDF resource denied.');
    }
}
