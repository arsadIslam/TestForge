<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
