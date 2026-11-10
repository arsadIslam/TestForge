<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown by services when a request is valid but breaks a business rule
 * ("this question is used in a test", "no attempts left", ...).
 *
 * Laravel calls render() automatically, so controllers need no try/catch:
 *  - web request → redirect back with the message flashed as 'error' (and the old input)
 *  - API request (or any request expecting JSON) → 422 JSON { "message": "..." }
 *
 * ShouldntReport: these are expected user-facing outcomes, not bugs, so they are not logged.
 */
class BusinessRuleException extends RuntimeException implements ShouldntReport
{
    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
