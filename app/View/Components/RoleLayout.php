<?php

namespace App\View\Components;

use App\Enums\UserRole;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Shared base for the per-role layouts (<x-admin-layout>, <x-teacher-layout>,
 * <x-student-layout>). They all render the same HTML shell (layouts.app); what
 * differs per role is the navigation menu (config/navigation.php) and the area badge.
 */
abstract class RoleLayout extends Component
{
    abstract protected function role(): UserRole;

    public function render(): View
    {
        $role = $this->role();

        return view('layouts.app', [
            'role' => $role,
            'navLinks' => config("navigation.{$role->value}", []),
        ]);
    }
}
