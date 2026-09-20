<?php

namespace Tests\Feature\Domain\Authoring;

use App\Enums\QuestionType;
use App\Enums\QuizReviewPolicy;
use App\Enums\QuizStatus;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuizDomainTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_quiz_structure_is_typed_owned_and_ordered(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create([
            'review_policy' => QuizReviewPolicy::AfterClose,
            'shuffle_questions' => true,
        ]);
        $secondQuestion = Question::factory()->for($quiz)->create([
            'prompt' => 'Second question',
            'position' => 2,
        ]);
        $firstQuestion = Question::factory()->trueFalse()->for($quiz)->create([
            'prompt' => 'First question',
            'position' => 1,
        ]);
        AnswerOption::factory()->for($firstQuestion)->create([
            'content' => 'False',
            'position' => 2,
        ]);
        AnswerOption::factory()->correct()->for($firstQuestion)->create([
            'content' => 'True',
            'position' => 1,
        ]);

        $quiz->refresh();

        $this->assertTrue($quiz->educator->is($educator));
        $this->assertSame(QuizStatus::Draft, $quiz->status);
        $this->assertSame(QuizReviewPolicy::AfterClose, $quiz->review_policy);
        $this->assertTrue($quiz->shuffle_questions);
        $this->assertSame(
            [$firstQuestion->id, $secondQuestion->id],
            $quiz->questions->modelKeys(),
        );
        $this->assertSame(QuestionType::TrueFalse, $quiz->questions->firstOrFail()->type);
        $this->assertSame(
            ['True', 'False'],
            $quiz->questions->firstOrFail()->answerOptions->pluck('content')->all(),
        );
        $this->assertTrue($quiz->questions->firstOrFail()->answerOptions->firstOrFail()->is_correct);
    }

    #[DataProvider('lifecycleTransitions')]
    public function test_lifecycle_allows_only_defined_transitions(
        QuizStatus $from,
        QuizStatus $to,
        bool $expected,
    ): void {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return array<string, array{QuizStatus, QuizStatus, bool}>
     */
    public static function lifecycleTransitions(): array
    {
        return [
            'draft can be scheduled' => [QuizStatus::Draft, QuizStatus::Scheduled, true],
            'draft can be published' => [QuizStatus::Draft, QuizStatus::Published, true],
            'scheduled can return to draft' => [QuizStatus::Scheduled, QuizStatus::Draft, true],
            'scheduled can be published' => [QuizStatus::Scheduled, QuizStatus::Published, true],
            'published can be closed' => [QuizStatus::Published, QuizStatus::Closed, true],
            'closed can be archived' => [QuizStatus::Closed, QuizStatus::Archived, true],
            'published cannot return to draft' => [QuizStatus::Published, QuizStatus::Draft, false],
            'closed cannot be republished' => [QuizStatus::Closed, QuizStatus::Published, false],
            'archived is terminal' => [QuizStatus::Archived, QuizStatus::Draft, false],
        ];
    }

    public function test_soft_deleting_quiz_preserves_structure_until_permanent_deletion(): void
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->for($quiz)->create();
        $answerOption = AnswerOption::factory()->for($question)->create();

        $quiz->delete();

        $this->assertSoftDeleted($quiz);
        $this->assertModelExists($question);
        $this->assertModelExists($answerOption);

        $quiz->forceDelete();

        $this->assertModelMissing($quiz);
        $this->assertModelMissing($question);
        $this->assertModelMissing($answerOption);
    }

    public function test_mass_assignment_cannot_move_authoring_records_between_owners(): void
    {
        $owner = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($owner, 'educator')->create();
        $otherQuiz = Quiz::factory()->for($otherEducator, 'educator')->create();
        $question = Question::factory()->for($quiz)->create();
        $otherQuestion = Question::factory()->for($otherQuiz)->create();
        $answerOption = AnswerOption::factory()->for($question)->create();

        $quiz->fill(['educator_id' => $otherEducator->id]);
        $question->fill(['quiz_id' => $otherQuiz->id]);
        $answerOption->fill(['question_id' => $otherQuestion->id]);

        $this->assertSame($owner->id, $quiz->educator_id);
        $this->assertSame($quiz->id, $question->quiz_id);
        $this->assertSame($question->id, $answerOption->question_id);
    }
}
