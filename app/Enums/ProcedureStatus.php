<?php

namespace App\Enums;

enum ProcedureStatus: string
{
    case Draft = 'draft';
    case Reviewed = 'reviewed';
    case Published = 'published';

    public function label(): string
    {
        return __('enums.procedure_status.'.$this->value);
    }
}
