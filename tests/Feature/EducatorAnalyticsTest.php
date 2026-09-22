<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EducatorAnalyticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_sees_real_summary_trend_and_question_analysis(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create(['title' => 'Biology essentials']);
        [$sourceQuestion, $correctOption, $distractor] = $this->sourceQuestion($quiz);
        $passedAttempt = $this->completedAttempt($quiz, 8, true, '2026-09-20 09:00:00', 10);
        $failedAttempt = $this->completedAttempt($quiz, 4, false, '2026-09-21 09:00:00', 20);
        $this->snapshotAnswer($passedAttempt, $sourceQuestion, $correctOption, $distractor, $correctOption);
        $this->snapshotAnswer($failedAttempt, $sourceQuestion, $correctOption, $distractor, $distractor);
        QuizAttempt::factory()->for($quiz)->create([
            'status' => QuizAttemptStatus::InProgress,
            'started_at' => '2026-09-21 10:00:00',
            'score' => null,
            'passed' => null,
        ]);

        $response = $this->actingAs($educator)->get(route('educator.analytics.index'));

        $response->assertOk()
            ->assertSeeTextInOrder(['Attempts', '3', 'Completion', '67%', 'Average score', '60%', 'Best 80%', 'Pass rate', '50%', 'Average 15 min'])
            ->assertSeeText('Biology essentials')
            ->assertSeeText('Which cell structure produces energy?')
            ->assertSeeText('50%')
            ->assertSeeText('Mitochondrion · correct')
            ->assertSeeText('Nucleus');
    }

    public function test_quiz_and_date_filters_exclude_nonmatching_attempts(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $selectedQuiz = Quiz::factory()->published()->for($educator, 'educator')->create(['title' => 'Selected quiz']);
        $otherQuiz = Quiz::factory()->published()->for($educator, 'educator')->create(['title' => 'Other quiz']);
        $this->completedAttempt($selectedQuiz, 9, true, '2026-09-15 10:00:00', 5);
        $this->completedAttempt($selectedQuiz, 5, false, '2026-08-01 10:00:00', 5);
        $this->completedAttempt($otherQuiz, 2, false, '2026-09-15 10:00:00', 5);

        $response = $this->actingAs($educator)->get(route('educator.analytics.index', [
            'quiz_id' => $selectedQuiz->getKey(),
            'from' => '2026-09-01',
            'to' => '2026-09-30',
        ]));

        $response->assertOk()
            ->assertSeeTextInOrder(['Attempts', '1', 'Completion', '100%', 'Average score', '90%']);
    }

    public function test_other_educators_quiz_is_rejected_by_the_filter(): void
    {
        $educator = User::factory()->educator()->create();
        $otherQuiz = Quiz::factory()->published()->for(User::factory()->educator(), 'educator')->create();

        $this->actingAs($educator)
            ->get(route('educator.analytics.index', ['quiz_id' => $otherQuiz->getKey()]))
            ->assertRedirect()
            ->assertSessionHasErrors('quiz_id');
    }

    public function test_date_range_longer_than_one_year_is_rejected(): void
    {
        $educator = User::factory()->educator()->create();

        $this->actingAs($educator)
            ->get(route('educator.analytics.index', ['from' => '2024-01-01', 'to' => '2026-01-02']))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'to' => 'Choose a date range of one year or less.',
            ]);
    }

    public function test_learner_cannot_access_educator_analytics(): void
    {
        $this->actingAs(User::factory()->learner()->create())
            ->get(route('educator.analytics.index'))
            ->assertForbidden();
    }

    public function test_empty_analytics_has_a_clear_next_step(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();

        $this->actingAs($educator)
            ->get(route('educator.analytics.index'))
            ->assertSeeText('No attempts match these filters.')
            ->assertSeeText('Publish an assessment or broaden the selected date range.');
    }

    /** @return array{Question, AnswerOption, AnswerOption} */
    private function sourceQuestion(Quiz $quiz): array
    {
        $question = Question::factory()->for($quiz)->create([
            'prompt' => 'Which cell structure produces energy?',
            'points' => 2,
        ]);
        $correctOption = AnswerOption::factory()->correct()->for($question)->create([
            'content' => 'Mitochondrion',
            'position' => 1,
        ]);
        $distractor = AnswerOption::factory()->for($question)->create([
            'content' => 'Nucleus',
            'position' => 2,
        ]);

        return [$question, $correctOption, $distractor];
    }

    private function completedAttempt(
        Quiz $quiz,
        int $score,
        bool $passed,
        string $startedAt,
        int $durationMinutes,
    ): QuizAttempt {
        return QuizAttempt::factory()->for($quiz)->create([
            'status' => QuizAttemptStatus::Submitted,
            'started_at' => $startedAt,
            'submitted_at' => Carbon::parse($startedAt)->addMinutes($durationMinutes),
            'max_score' => 10,
            'score' => $score,
            'passed' => $passed,
        ]);
    }

    private function snapshotAnswer(
        QuizAttempt $attempt,
        Question $sourceQuestion,
        AnswerOption $correctOption,
        AnswerOption $distractor,
        AnswerOption $selectedSourceOption,
    ): void {
        $question = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create([
            'source_question_id' => $sourceQuestion->getKey(),
            'prompt' => $sourceQuestion->prompt,
            'points' => $sourceQuestion->points,
        ]);
        $correctSnapshot = QuizAttemptOption::factory()->for($question, 'question')->create([
            'source_answer_option_id' => $correctOption->getKey(),
            'content' => $correctOption->content,
            'is_correct' => true,
            'position' => 1,
        ]);
        $distractorSnapshot = QuizAttemptOption::factory()->for($question, 'question')->create([
            'source_answer_option_id' => $distractor->getKey(),
            'content' => $distractor->content,
            'is_correct' => false,
            'position' => 2,
        ]);
        $selectedSnapshot = $selectedSourceOption->is($correctOption) ? $correctSnapshot : $distractorSnapshot;
        $answer = new QuizAttemptAnswer(['answered_at' => $attempt->submitted_at]);
        $answer->attempt()->associate($attempt);
        $answer->question()->associate($question);
        $answer->option()->associate($selectedSnapshot);
        $answer->save();
    }
}
