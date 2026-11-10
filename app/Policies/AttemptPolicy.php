<?php

namespace App\Policies;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\User;

class AttemptPolicy
{
    /**
     * The student who made it, the teacher who owns the test, or an admin.
     */
    public function view(User $user, Attempt $attempt): bool
    {
        if ($user->isAdmin() || $this->isOwner($user, $attempt)) {
            return true;
        }

        return $user->isTeacher() && $attempt->test->created_by === $user->id;
    }

    /**
     * The expires_at check is NOT here: it is re-checked inside AttemptService under a
     * row lock, so a request that passes this check one millisecond early still fails.
     */
    public function saveAnswer(User $user, Attempt $attempt): bool
    {
        return $this->isOwner($user, $attempt) && $attempt->status === AttemptStatus::InProgress;
    }

    public function submit(User $user, Attempt $attempt): bool
    {
        return $this->isOwner($user, $attempt) && $attempt->status === AttemptStatus::InProgress;
    }

    private function isOwner(User $user, Attempt $attempt): bool
    {
        return $user->isStudent() && $attempt->user_id === $user->id;
    }
}
