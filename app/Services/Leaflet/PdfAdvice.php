<?php

namespace App\Services\Leaflet;

use App\Enums\AdviceSection;
use App\Models\AdviceItem;
use App\Models\Procedure;

final class PdfAdvice
{
    /** @return list<array{item: AdviceItem, procedure: ?Procedure, theme: ?ThemeColor}> */
    public function redFlags(LeafletDocument $doc): array
    {
        $general = [];
        $specific = [];
        $seen = [];
        $sharedKeys = SharedAdvice::keys($doc->sections, true);
        foreach ($doc->sections as ['procedure' => $procedure, 'theme' => $theme]) {
            foreach ($procedure->adviceItems->where('section', AdviceSection::RedFlag) as $item) {
                if (trim($item->getTranslation('text', $doc->locale)) === '') {
                    continue;
                }
                $key = $item->merge_key;
                if ($key !== null && $key !== '') {
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                }
                if (in_array($key, $sharedKeys, true) || str_starts_with($key ?? '', 'general.')) {
                    $general[] = ['item' => $item, 'procedure' => null, 'theme' => null];
                } else {
                    $specific[] = compact('item', 'procedure', 'theme');
                }
            }
        }

        return [...$general, ...$specific];
    }

    /** @return list<array{section: string, icon: string, paragraphs: list<string>}> */
    public function topics(?Procedure $procedure, string $locale, ?LeafletDocument $doc = null): array
    {
        $topics = [];
        foreach (['pain' => 'pill', 'eating' => 'cup', 'hygiene' => 'droplet', 'activity' => 'run', 'sleep' => 'moon'] as $section => $icon) {
            $items = $doc
                ? ($procedure ? $doc->advice($procedure, AdviceSection::from($section)) : ($doc->general()[$section] ?? collect()))
                : $procedure->adviceItems->where('section', AdviceSection::from($section));
            $paragraphs = $items
                ->map(fn ($item): string => trim($item->getTranslation('text', $locale)))
                ->filter(fn (string $text): bool => $text !== '')->values()->all();
            if ($paragraphs === []) {
                continue;
            }
            if ($section === 'pain' && (! $procedure || ! isset($doc?->general()['pain']))) {
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
