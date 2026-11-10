<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * GET /dashboard → the dashboard for the user's role.
 *
 * Breeze's auth controllers (email verification, password confirm, ...) all
 * redirect to route('dashboard'); this keeps every one of them role-aware
 * without editing each controller.
 */
class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->role->homeRoute());
    }
}
