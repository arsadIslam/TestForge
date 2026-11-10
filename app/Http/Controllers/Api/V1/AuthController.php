<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Sanctum personal access tokens: the client sends "Authorization: Bearer <token>".
 */
class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register → 201 + token. New users are always students.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'), // hashed by the 'hashed' cast
        ]);

        event(new Registered($user));

        return $this->tokenResponse($user, $request->validated('device_name'), 201);
    }

    /**
     * POST /api/v1/auth/login → 200 + token; wrong credentials → 422; deactivated → 403.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Same message whether the email or the password is wrong, so the API can't be
        // used to find out which emails are registered.
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        abort_unless($user->is_active, 403, 'Your account has been deactivated.');

        return $this->tokenResponse($user, $request->validated('device_name'));
    }

    /**
     * POST /api/v1/auth/logout → 204. Revokes only the token used for this request.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * GET /api/v1/me
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function tokenResponse(User $user, ?string $deviceName, int $status = 200): JsonResponse
    {
        $token = $user->createToken($deviceName ?: 'api');

        return response()->json([
            'token' => $token->plainTextToken, // shown once; only a hash is stored
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], $status);
    }
}
