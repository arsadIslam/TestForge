<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        // enum() returns null for a missing or invalid ?role=, so a bad value just means "no filter".
        $role = $request->enum('role', UserRole::class);
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->when($role, fn (Builder $q) => $q->where('role', $role))
            // Grouped in a closure → WHERE role = ? AND (name LIKE ? OR email LIKE ?).
            // Without the closure the OR would escape the role filter.
            ->when($search !== '', fn (Builder $q) => $q->where(
                fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString(); // keep filters in the pagination links

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'filters' => ['role' => $role?->value, 'search' => $search],
        ]);
    }

    /**
     * Route guard: ->can('update', 'user') — admins only, and never their own account.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        // role is not mass-assignable (see User::$fillable), so it is set explicitly.
        $user->role = $request->enum('role', UserRole::class);
        $user->save();

        return back()->with('success', "{$user->name} is now a {$user->role->label()}.");
    }

    /**
     * Route guard: ->can('update', 'user'). A deactivated user cannot log in, and
     * EnsureRole blocks any session they already have on the next request.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $user->is_active = ! $user->is_active;
        $user->save();

        if (! $user->is_active) {
            // Also cut off the REST API immediately: revoke every Sanctum token they hold.
            $user->tokens()->delete();
        }

        $state = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->name} has been {$state}.");
    }
}
