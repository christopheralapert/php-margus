<?php

namespace App\Enums;

enum AdviceSection: string
{
    case Do = 'do';
    case Dont = 'dont';
    case Pain = 'pain';
    case Eating = 'eating';
    case Hygiene = 'hygiene';
    case Activity = 'activity';
    case Sleep = 'sleep';
    case RedFlag = 'red_flag';

    public function label(): string
    {
        return __('enums.advice_section.'.$this->value);
    }
}
