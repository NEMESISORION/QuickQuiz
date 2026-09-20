<?php

namespace Tests\Unit\Policies;

use App\Enums\QuizStatus;
use App\Enums\UserRole;
use App\Models\Quiz;
use App\Models\User;
use App\Policies\QuizPolicy;
use PHPUnit\Framework\TestCase;

class QuizPolicyTest extends TestCase
{
    public function test_educator_can_list_and_create_quizzes(): void
    {
        $educator = $this->user(10, UserRole::Educator);
        $policy = new QuizPolicy;

        $this->assertTrue($policy->viewAny($educator));
        $this->assertTrue($policy->create($educator));
    }

    public function test_learner_cannot_list_or_create_quizzes(): void
    {
        $learner = $this->user(10, UserRole::Learner);
        $policy = new QuizPolicy;

        $this->assertFalse($policy->viewAny($learner));
        $this->assertFalse($policy->create($learner));
    }

    public function test_owner_can_manage_draft_quiz(): void
    {
        $educator = $this->user(10, UserRole::Educator);
        $quiz = $this->quiz(10, QuizStatus::Draft);
        $policy = new QuizPolicy;

        $this->assertTrue($policy->view($educator, $quiz));
        $this->assertTrue($policy->update($educator, $quiz));
        $this->assertTrue($policy->delete($educator, $quiz));
        $this->assertTrue($policy->publish($educator, $quiz));
        $this->assertTrue($policy->archive($educator, $quiz));
    }

    public function test_other_educator_cannot_manage_quiz(): void
    {
        $educator = $this->user(20, UserRole::Educator);
        $quiz = $this->quiz(10, QuizStatus::Draft);
        $policy = new QuizPolicy;

        $this->assertFalse($policy->view($educator, $quiz));
        $this->assertFalse($policy->update($educator, $quiz));
        $this->assertFalse($policy->delete($educator, $quiz));
        $this->assertFalse($policy->publish($educator, $quiz));
        $this->assertFalse($policy->archive($educator, $quiz));
    }

    public function test_published_quiz_cannot_be_edited_or_deleted(): void
    {
        $educator = $this->user(10, UserRole::Educator);
        $quiz = $this->quiz(10, QuizStatus::Published);
        $policy = new QuizPolicy;

        $this->assertTrue($policy->view($educator, $quiz));
        $this->assertFalse($policy->update($educator, $quiz));
        $this->assertFalse($policy->delete($educator, $quiz));
        $this->assertFalse($policy->publish($educator, $quiz));
        $this->assertTrue($policy->archive($educator, $quiz));
    }

    public function test_only_owner_can_restore_and_nobody_can_force_delete(): void
    {
        $owner = $this->user(10, UserRole::Educator);
        $otherEducator = $this->user(20, UserRole::Educator);
        $quiz = $this->quiz(10, QuizStatus::Draft);
        $quiz->setRawAttributes([
            ...$quiz->getAttributes(),
            'deleted_at' => '2026-09-20 12:00:00',
        ], true);
        $policy = new QuizPolicy;

        $this->assertTrue($policy->restore($owner, $quiz));
        $this->assertFalse($policy->restore($otherEducator, $quiz));
        $this->assertFalse($policy->forceDelete($owner, $quiz));
    }

    private function user(int $id, UserRole $role): User
    {
        $user = new User;
        $user->id = $id;
        $user->role = $role;

        return $user;
    }

    private function quiz(int $educatorId, QuizStatus $status): Quiz
    {
        $quiz = new Quiz([
            'status' => $status,
        ]);
        $quiz->educator_id = $educatorId;

        return $quiz;
    }
}
