<?php

namespace App\View\Components;

use App\Enums\UserRole;

class StudentLayout extends RoleLayout
{
    protected function role(): UserRole
    {
        return UserRole::Student;
    }
}
