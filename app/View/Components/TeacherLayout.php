<?php

namespace App\View\Components;

use App\Enums\UserRole;

class TeacherLayout extends RoleLayout
{
    protected function role(): UserRole
    {
        return UserRole::Teacher;
    }
}
