<?php

namespace App\View\Components;

use App\Enums\UserRole;

class AdminLayout extends RoleLayout
{
    protected function role(): UserRole
    {
        return UserRole::Admin;
    }
}
