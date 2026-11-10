<?php

use App\Enums\UserRole;
use App\Models\Test;
use App\Models\User;

/*
| REST API (/api/v1, Sanctum tokens). Requests deliberately omit the Accept header
| (->get(), not ->getJson()) where the point is that errors are JSON anyway.
*/

function token(User $user): array
{
    return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
}

it('logs in and returns a token', function () {
    $student = User::factory()->student()->create();

    $this->postJson('/api/v1/auth/login', ['email' => $student->email, 'password' => 'password', 'device_name' => 'phone'])
        ->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email', 'role']])
        ->assertJsonMissingPath('user.password');

    expect($student->tokens()->value('name'))->toBe('phone');
});

it('rejects bad credentials with 422 and a deactivated user with 403', function () {
    $student = User::factory()->student()->create();
    $this->postJson('/api/v1/auth/login', ['email' => $student->email, 'password' => 'wrong'])->assertStatus(422)->assertJsonValidationErrors('email');

    $inactive = User::factory()->student()->inactive()->create();
    $this->postJson('/api/v1/auth/login', ['email' => $inactive->email, 'password' => 'password'])->assertForbidden();
});

it('registers a student (201), whatever role is sent', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Api User', 'email' => 'api@example.com',
        'password' => 'password123', 'password_confirmation' => 'password123', 'role' => 'admin',
    ])->assertCreated()->assertJsonPath('user.role', 'student');

    expect(User::where('email', 'api@example.com')->value('role'))->toBe(UserRole::Student);
});

it('returns 401 JSON without a token, even without an Accept header', function (string $url) {
    $this->get($url)->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
})->with(['/api/v1/tests', '/api/v1/me/results', '/api/v1/me/analytics', '/api/v1/me', '/api/v1/tests/1/leaderboard']);

it('returns 403 when a teacher calls a student endpoint', function () {
    $this->get('/api/v1/me/results', token(User::factory()->teacher()->create()))
        ->assertForbidden()->assertJson(['message' => 'You do not have access to this area.']);
});

it('lists available tests with my attempts left, paginated', function () {
    $test = publishedTest();
    Test::factory()->create(); // a draft — must not be listed
    $student = User::factory()->student()->create();

    $this->get('/api/v1/tests', token($student))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $test->id)
        ->assertJsonPath('data.0.questions_count', 2)
        ->assertJsonPath('data.0.attempts_left', 2)
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'total']]);
});

it('returns the leaderboard with my own rank, and 404 JSON for an unknown test', function () {
    $test = publishedTest();
    $me = User::factory()->student()->create(['name' => 'Me']);
    resultFor($test, User::factory()->student()->create(), 9, 100);
    resultFor($test, $me, 4, 100);

    $this->get("/api/v1/tests/{$test->id}/leaderboard", token($me))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.rank', 1)
        ->assertJsonPath('me.name', 'Me')
        ->assertJsonPath('me.rank', 2);

    $this->get('/api/v1/tests/999999/leaderboard', token($me))->assertNotFound()->assertExactJson(['message' => 'Not found.']);
});

it('returns my results and my subject analytics', function () {
    $test = publishedTest();
    $me = User::factory()->student()->create();
    resultFor($test, $me, 4, 100);

    $this->get('/api/v1/me/results', token($me))
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.score', 4)->assertJsonPath('data.0.test.id', $test->id);

    $this->get('/api/v1/me/analytics', token($me))
        ->assertOk()->assertJsonStructure(['data', 'summary' => ['results', 'tests', 'avg_percent', 'best_percent', 'pass_rate']]);
});

it('logs out by revoking only the current token (204)', function () {
    $student = User::factory()->student()->create();
    $headers = token($student);
    $student->createToken('other-device');

    $this->post('/api/v1/auth/logout', [], $headers)->assertNoContent();

    expect($student->tokens()->pluck('name')->all())->toBe(['other-device']);
});
