<?php

namespace App\Enums;

enum QuestionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single choice',
            self::Multiple => 'Multiple choice',
        };
    }
}
