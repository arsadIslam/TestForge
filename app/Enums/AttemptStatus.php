<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case AutoSubmitted = 'auto_submitted';
    case Evaluated = 'evaluated';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
