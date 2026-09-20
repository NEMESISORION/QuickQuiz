<?php

namespace Tests\Unit\Policies;

use App\Enums\QuizStatus;
use App\Enums\UserRole;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Policies\QuestionPolicy;
use PHPUnit\Framework\TestCase;

class QuestionPolicyTest extends TestCase
{
    public function test_owner_can_manage_questions_in_draft_quiz(): void
    {
        $educator = $this->user(10, UserRole::Educator);
        $quiz = $this->quiz(10, QuizStatus::Draft);
        $question = $this->question($quiz);
        $policy = new QuestionPolicy;

        $this->assertTrue($policy->viewAny($educator));
        $this->assertTrue($policy->view($educator, $question));
        $this->assertTrue($policy->create($educator, $quiz));
        $this->assertTrue($policy->update($educator, $question));
        $this->assertTrue($policy->delete($educator, $question));
    }

    public function test_other_educator_cannot_manage_questions(): void
    {
        $educator = $this->user(20, UserRole::Educator);
        $quiz = $this->quiz(10, QuizStatus::Draft);
        $question = $this->question($quiz);
        $policy = new QuestionPolicy;

        $this->assertFalse($policy->view($educator, $question));
        $this->assertFalse($policy->create($educator, $quiz));
        $this->assertFalse($policy->update($educator, $question));
        $this->assertFalse($policy->delete($educator, $question));
    }

    public function test_owner_cannot_change_questions_after_publication(): void
    {
        $educator = $this->user(10, UserRole::Educator);
        $quiz = $this->quiz(10, QuizStatus::Published);
        $question = $this->question($quiz);
        $policy = new QuestionPolicy;

        $this->assertTrue($policy->view($educator, $question));
        $this->assertFalse($policy->create($educator, $quiz));
        $this->assertFalse($policy->update($educator, $question));
        $this->assertFalse($policy->delete($educator, $question));
    }

    public function test_learner_cannot_access_authoring_questions(): void
    {
        $learner = $this->user(10, UserRole::Learner);
        $quiz = $this->quiz(10, QuizStatus::Draft);
        $question = $this->question($quiz);
        $policy = new QuestionPolicy;

        $this->assertFalse($policy->viewAny($learner));
        $this->assertFalse($policy->view($learner, $question));
        $this->assertFalse($policy->create($learner, $quiz));
        $this->assertFalse($policy->update($learner, $question));
        $this->assertFalse($policy->delete($learner, $question));
    }

    public function test_questions_cannot_be_restored_or_force_deleted_directly(): void
    {
        $educator = $this->user(10, UserRole::Educator);
        $question = $this->question($this->quiz(10, QuizStatus::Draft));
        $policy = new QuestionPolicy;

        $this->assertFalse($policy->restore($educator, $question));
        $this->assertFalse($policy->forceDelete($educator, $question));
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

    private function question(Quiz $quiz): Question
    {
        $question = new Question;
        $question->setRelation('quiz', $quiz);

        return $question;
    }
}
