<?php

namespace App\Policies;

use App\Enums\TestStatus;
use App\Models\Test;
use App\Models\User;

/**
 * Teachers manage only their own tests. Once a test is published its content is
 * frozen (students may already have results based on it); it can only be archived.
 *
 * Whether a student may START a test (published, in window, attempts left) is
 * decided in AttemptService, because it needs a row lock and an attempt count.
 */
class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Test $test): bool
    {
        return $this->isOwner($user, $test) || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * Covers editing details and attaching/reordering questions.
     */
    public function update(User $user, Test $test): bool
    {
        return $this->isOwner($user, $test) && $test->status === TestStatus::Draft;
    }

    public function delete(User $user, Test $test): bool
    {
        return $this->isOwner($user, $test) && $test->status === TestStatus::Draft;
    }

    public function publish(User $user, Test $test): bool
    {
        return $this->isOwner($user, $test) && $test->status === TestStatus::Draft;
    }

    public function archive(User $user, Test $test): bool
    {
        return $this->isOwner($user, $test) && $test->status === TestStatus::Published;
    }

    /**
     * Teachers see analytics for their own tests; admins see all reports.
     */
    public function viewAnalytics(User $user, Test $test): bool
    {
        return $this->isOwner($user, $test) || $user->isAdmin();
    }

    private function isOwner(User $user, Test $test): bool
    {
        return $user->isTeacher() && $test->created_by === $user->id;
    }
}
