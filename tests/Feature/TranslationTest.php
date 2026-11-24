<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    public function test_both_locales_have_matching_translation_keys_and_placeholders(): void
    {
        $englishFiles = glob(lang_path('en/*.php'));
        $this->assertCount(count($englishFiles), glob(lang_path('et/*.php')));

        foreach ($englishFiles as $file) {
            $english = Arr::dot(require $file);
            $estonian = Arr::dot(require lang_path('et/'.basename($file)));
            $this->assertSame(array_keys($english), array_keys($estonian), basename($file));

            foreach ($english as $key => $value) {
                if (is_string($value)) {
                    preg_match_all('/:[a-z_]+/i', $value, $expected);
                    preg_match_all('/:[a-z_]+/i', $estonian[$key], $actual);
                    sort($expected[0]);
                    sort($actual[0]);
                    $this->assertSame($expected[0], $actual[0], basename($file).':'.$key);
                }
            }
        }
    }
}
