<?php

namespace App\Enums;

enum TimelinePhase: string
{
    case First24h = 'first_24h';
    case Days1To3 = 'days_1_3';
    case Week1 = 'week_1';
    case FullRecovery = 'full_recovery';

    public function label(): string
    {
        return __('enums.timeline_phase.'.$this->value);
    }
}
