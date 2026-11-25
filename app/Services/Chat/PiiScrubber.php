<?php

namespace App\Services\Chat;

final class PiiScrubber
{
    public function scrub(string $text, ?string $firstName = null): string
    {
        $patterns = [
            '/[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.[\p{L}]{2,}/u' => '[email]',
            '/(?<!\d)[1-6]\d{10}(?!\d)/u' => '[id]',
            '/(?<!\d)(?:\d{1,2}\.\d{1,2}\.\d{4}|\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})(?!\d)/u' => '[date]',
            '/(?<![\p{L}\p{N}])(?:\+|00)\d(?:[ ()\-]*\d){6,14}(?!\d)/u' => '[phone]',
            '/(?<![\p{L}\p{N}])\d(?:[ ()\-]*\d){4,14}(?![\p{L}\p{N}])/u' => '[phone]',
        ];

        $text = preg_replace(array_keys($patterns), array_values($patterns), $text);

        if ($firstName !== null && trim($firstName) !== '') {
            $text = preg_replace('/(?<!\p{L})'.preg_quote(trim($firstName), '/').'(?!\p{L})/iu', '[name]', $text);
        }

        return $text;
    }
}
