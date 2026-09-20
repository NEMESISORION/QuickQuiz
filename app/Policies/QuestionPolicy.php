<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Educator);
    }

    public function view(User $user, Question $question): bool
    {
        return $this->ownsQuiz($user, $question->quiz);
    }

    public function create(User $user, Quiz $quiz): bool
    {
        return $this->ownsEditableQuiz($user, $quiz);
    }

    public function update(User $user, Question $question): bool
    {
        return $this->ownsEditableQuiz($user, $question->quiz);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->ownsEditableQuiz($user, $question->quiz);
    }

    public function restore(User $user, Question $question): bool
    {
        return false;
    }

    public function forceDelete(User $user, Question $question): bool
    {
        return false;
    }

    private function ownsEditableQuiz(User $user, Quiz $quiz): bool
    {
        return $quiz->status->isEditable() && $this->ownsQuiz($user, $quiz);
    }

    private function ownsQuiz(User $user, Quiz $quiz): bool
    {
        return $user->hasRole(UserRole::Educator) && $quiz->educator_id === $user->getKey();
    }
}
