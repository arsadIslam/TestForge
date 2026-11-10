<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

/**
 * The question bank is shared between teachers: any teacher can browse it and
 * attach any question to their tests, but only the author can change or delete it.
 */
class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Question $question): bool
    {
        return $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Question $question): bool
    {
        return $this->isAuthor($user, $question);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->isAuthor($user, $question);
    }

    private function isAuthor(User $user, Question $question): bool
    {
        return $user->isTeacher() && $question->created_by === $user->id;
    }
}
