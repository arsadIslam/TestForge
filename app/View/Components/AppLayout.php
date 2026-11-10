<?php

namespace App\View\Components;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

/**
 * Breeze's <x-app-layout>, kept for pages every role shares (e.g. Profile):
 * it shows the navigation of whoever is logged in.
 */
class AppLayout extends RoleLayout
{
    protected function role(): UserRole
    {
        return Auth::user()->role;
    }
}
