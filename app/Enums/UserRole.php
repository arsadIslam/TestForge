<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Named route each role lands on after login.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Teacher => 'teacher.dashboard',
            self::Student => 'student.dashboard',
        };
    }
}
