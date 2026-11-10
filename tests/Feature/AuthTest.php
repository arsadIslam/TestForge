<?php

use App\Enums\UserRole;
use App\Models\User;

dataset('roles', ['admin', 'teacher', 'student']);

it('sends each role to its own dashboard after login', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route("{$role}.dashboard", absolute: false));

    $this->get('/dashboard')->assertRedirect(route("{$role}.dashboard"));
})->with('roles');

it('lets a role into its own area only', function (string $role, string $area) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get("/{$area}")->assertStatus($role === $area ? 200 : 403);
})->with('roles')->with(['admin', 'teacher', 'student']);

it('sends guests to the login page', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with(['/admin', '/teacher', '/student', '/dashboard', '/teacher/questions', '/student/tests']);

it('does not let a deactivated user log in', function () {
    $user = User::factory()->student()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Your account has been deactivated. Please contact the administrator.']);

    $this->assertGuest();
});

it('blocks a user deactivated in the middle of a session', function () {
    $user = User::factory()->teacher()->create();
    $this->actingAs($user)->get('/teacher')->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user->fresh())->get('/teacher')->assertForbidden();
});

it('never lets registration choose a role', function () {
    $this->post('/register', [
        'name' => 'Mallory', 'email' => 'mallory@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
        'role' => 'admin',
    ])->assertRedirect(route('student.dashboard', absolute: false));

    expect(User::where('email', 'mallory@example.com')->value('role'))->toBe(UserRole::Student);
});

it('stops an admin changing their own role or status', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/admin/users/{$admin->id}/role", ['role' => 'student'])->assertForbidden();
    $this->actingAs($admin)->patch("/admin/users/{$admin->id}/status")->assertForbidden();
});

it('lets an admin deactivate a user, which also revokes their API tokens', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    $student->createToken('phone');

    $this->actingAs($admin)->patch("/admin/users/{$student->id}/status")->assertSessionHas('success');

    expect($student->fresh()->is_active)->toBeFalse()
        ->and($student->tokens()->count())->toBe(0);
});
