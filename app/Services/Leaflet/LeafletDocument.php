<?php

namespace App\Services\Leaflet;

use App\Enums\ProcedureStatus;
use App\Models\AdviceItem;
use App\Models\Procedure;
use Carbon\CarbonImmutable;

final readonly class LeafletDocument
{
    /** @param list<array{procedure: Procedure, theme: ThemeColor}> $sections
     * @param  list<AdviceItem>  $general  Shared advice can be supplied here independently of procedure sections.
     */
    public function __construct(
        public string $locale,
        public ?string $firstName,
        public ?CarbonImmutable $date,
        public array $sections,
        public array $general = [],
    ) {}

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
        return $this->date?->locale($this->locale)->isoFormat($this->locale === 'et' ? 'DD.MM.YYYY' : 'D MMM YYYY');
    }
}
