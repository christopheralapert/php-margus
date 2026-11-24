<?php

namespace App\Services\Leaflet;

use App\Enums\AdviceSection;
use App\Models\Procedure;

final class PdfAdvice
{
    /** @return list<array{section: string, icon: string, paragraphs: list<string>}> */
    public function topics(Procedure $procedure, string $locale): array
    {
        $topics = [];
        foreach (['pain' => 'pill', 'eating' => 'cup', 'hygiene' => 'droplet', 'activity' => 'run', 'sleep' => 'moon'] as $section => $icon) {
            $paragraphs = $procedure->adviceItems->where('section', AdviceSection::from($section))
                ->map(fn ($item): string => trim($item->getTranslation('text', $locale)))
                ->filter(fn (string $text): bool => $text !== '')->values()->all();
            if ($paragraphs === []) {
                continue;
            }
            if ($section === 'pain') {
                $phrase = $this->normalize(__('pdf.medication_prescribed_phrase', [], $locale));
                $alreadyPrescribed = collect($paragraphs)->contains(fn (string $text): bool => str_contains($this->normalize($text), $phrase));
                if (! $alreadyPrescribed) {
                    $paragraphs[] = __('pdf.medication_advice', [], $locale);
                }
            }
            $topics[] = compact('section', 'icon', 'paragraphs');
        }

        return $topics;
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($text, 'UTF-8')));
    }
}
