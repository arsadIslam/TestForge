<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Change role / activate / deactivate. An admin cannot do this to themselves,
     * so the system can never be left with zero active admins by accident.
     */
    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $user->is($target);
    }
}
