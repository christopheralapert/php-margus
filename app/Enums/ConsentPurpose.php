<?php

namespace App\Enums;

enum ConsentPurpose: string
{
    case PersonalisedLeaflet = 'personalised_leaflet';
    case Account = 'account';
    case Chat = 'chat';

    public function label(): string
    {
        return __('enums.consent_purpose.'.$this->value);
    }
}
