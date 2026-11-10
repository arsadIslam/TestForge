<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level guard: "is this user allowed into this AREA of the app?"
 *
 * Usage: ->middleware('role:admin') or ->middleware('role:teacher,admin')
 *
 * Record-level questions ("may this teacher edit THIS test?") are answered by
 * Policies instead. The middleware is the coarse check, the policy the fine one.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // The 'auth' middleware runs first; this is only a safety net.
        abort_if($user === null, 401);

        // A user deactivated by an admin mid-session loses access on their next request.
        abort_unless($user->is_active, 403, 'Your account has been deactivated.');

        // UserRole::from() throws on a typo like 'role:techer', so a bad route definition fails loudly.
        $allowed = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless($user->hasRole(...$allowed), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
