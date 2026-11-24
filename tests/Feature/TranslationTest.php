<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    public function test_both_locales_have_matching_translation_keys_and_placeholders(): void
    {
        $files = [];

        foreach (['en', 'et'] as $locale) {
            $files[$locale] = collect(File::allFiles(lang_path($locale)))
                ->filter(fn ($file) => $file->getExtension() === 'php')
                ->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
                ->sort()->values()->all();
        }

        $this->assertSame($files['en'], $files['et'], 'Translation file sets differ.');

        foreach ($files['en'] as $file) {
            $this->assertTranslationParity(
                require lang_path('en/'.$file),
                require lang_path('et/'.$file),
                $file,
            );
        }
    }

    public function test_json_translations_have_matching_keys_and_placeholders(): void
    {
        $this->assertTranslationParity(
            json_decode(File::get(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR),
            json_decode(File::get(lang_path('et.json')), true, flags: JSON_THROW_ON_ERROR),
            'json',
        );
    }

    private function assertTranslationParity(array $english, array $estonian, string $path): void
    {
        $englishKeys = array_keys($english);
        $estonianKeys = array_keys($estonian);
        sort($englishKeys);
        sort($estonianKeys);
        $this->assertSame($englishKeys, $estonianKeys, $path);

        foreach ($english as $key => $value) {
            $keyPath = $path.'.'.$key;
            $this->assertSame(is_array($value), is_array($estonian[$key]), $keyPath);

            if (is_array($value)) {
                $this->assertTranslationParity($value, $estonian[$key], $keyPath);
            } elseif (is_string($value)) {
                $this->assertIsString($estonian[$key], $keyPath);
                preg_match_all('/:[a-z_]+/i', $value, $expected);
                preg_match_all('/:[a-z_]+/i', $estonian[$key], $actual);
                sort($expected[0]);
                sort($actual[0]);
                $this->assertSame($expected[0], $actual[0], $keyPath);
            }
        }
    }
}
