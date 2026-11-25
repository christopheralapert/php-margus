<?php

namespace App\Services\Chat;

use App\Enums\AdviceSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class RedFlagDetector
{
    public function detects(string $message, Collection $procedures, string $locale): bool
    {
        $message = ' '.$this->normalize($message).' ';
        $phrases = array_map($this->normalize(...), config('chat.red_flags'));

        // Slide 2–3 word windows over selected red-flag advice. Retain a window
        // only if it contains a configured symptom stem; no clinical inference.
        foreach ($procedures as $procedure) {
            foreach ($procedure->adviceItems as $item) {
                if ($item->section !== AdviceSection::RedFlag) {
                    continue;
                }
                $words = explode(' ', $this->normalize($item->getTranslation('text', $locale, false)));
                foreach ([2, 3] as $length) {
                    for ($i = 0; $i <= count($words) - $length; $i++) {
                        $phrase = implode(' ', array_slice($words, $i, $length));
                        foreach (config('chat.symptom_stems') as $stem) {
                            if (str_contains($phrase, $stem)) {
                                $phrases[] = $phrase;
                                break;
                            }
                        }
                    }
                }
            }
        }

        foreach ($phrases as $phrase) {
            if ($phrase !== '' && str_contains($message, ' '.$phrase.' ')) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::ascii(mb_strtolower($text))));
    }
}
