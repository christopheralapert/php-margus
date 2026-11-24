<?php

namespace App\Services\Leaflet;

use App\Enums\AdviceSection;

final class SharedAdvice
{
    /** @return list<string> */
    public static function keys(array $sections, bool $redFlags = false): array
    {
        $counts = [];
        foreach ($sections as ['procedure' => $procedure]) {
            $seen = [];
            foreach ($procedure->adviceItems as $item) {
                if (($item->section === AdviceSection::RedFlag) !== $redFlags || $item->merge_key === null || $item->merge_key === '') {
                    continue;
                }
                $seen[$item->merge_key] = true;
            }
            foreach (array_keys($seen) as $key) {
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        return array_keys(array_filter($counts, fn (int $count): bool => $count >= 2));
    }
}
