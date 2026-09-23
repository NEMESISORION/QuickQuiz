<?php

namespace App\Policies;

use App\Enums\QuizStatus;
use App\Enums\UserRole;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Educator);
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Educator);
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz) && $quiz->status->isEditable();
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz) && $quiz->status === QuizStatus::Draft;
    }

    public function restore(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && $quiz->getRawOriginal($quiz->getDeletedAtColumn()) !== null;
    }

    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return false;
    }

    public function publish(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && in_array($quiz->status, [QuizStatus::Draft, QuizStatus::Scheduled], true);
    }

    public function duplicate(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function archive(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz) && $quiz->status !== QuizStatus::Archived;
    }

    public function close(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz)
            && in_array($quiz->status, [QuizStatus::Scheduled, QuizStatus::Published], true);
    }

    public function discover(User $user, Quiz $quiz): bool
    {
        return $user->hasRole(UserRole::Learner)
            && in_array($quiz->status, [QuizStatus::Published, QuizStatus::Scheduled], true);
    }

    public function startAttempt(User $user, Quiz $quiz): bool
    {
        return $this->discover($user, $quiz);
    }

    public function viewResults(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    private function owns(User $user, Quiz $quiz): bool
    {
        return $user->hasRole(UserRole::Educator) && $quiz->educator_id === $user->getKey();
    }
}
