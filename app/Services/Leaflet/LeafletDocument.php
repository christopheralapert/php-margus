<?php

namespace App\Services\Leaflet;

use App\Enums\AdviceSection;
use App\Enums\ProcedureStatus;
use App\Models\AdviceItem;
use App\Models\Procedure;
use App\Support\DateFormat;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class LeafletDocument
{
    /** @param list<array{procedure: Procedure, theme: ThemeColor}> $sections
     * @param  list<AdviceItem>  $general
     * @param  list<string>  $sharedKeys
     */
    public function __construct(
        public string $locale,
        public ?string $firstName,
        public ?CarbonImmutable $date,
        public array $sections,
        public array $general = [],
        private array $sharedKeys = [],
    ) {}

    /** @return array<string, Collection<int, AdviceItem>> */
    public function general(): array
    {
        $groups = [];
        foreach (AdviceSection::cases() as $section) {
            $items = collect($this->general)->where('section', $section)->values();
            if ($section !== AdviceSection::RedFlag && $items->isNotEmpty()) {
                $groups[$section->value] = $items;
            }
        }

        return $groups;
    }

    /** @return Collection<int, AdviceItem> */
    public function advice(Procedure $procedure, AdviceSection $section): Collection
    {
        return $procedure->adviceItems->where('section', $section)
            ->reject(fn (AdviceItem $item): bool => $section !== AdviceSection::RedFlag && in_array($item->merge_key, $this->sharedKeys, true))->values();
    }

    public function hasDraft(): bool
    {
        foreach ($this->sections as $section) {
            if ($section['procedure']->status === ProcedureStatus::Draft) {
                return true;
            }
        }

        return false;
    }

    public function title(): string
    {
        return __('wizard.leaflet_title', [], $this->locale);
    }

    public function formattedDate(): ?string
    {
        return DateFormat::date($this->date, $this->locale);
    }
}
