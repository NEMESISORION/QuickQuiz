<?php

namespace Tests\Feature;

use App\Enums\QuizStatus;
use App\Models\AnswerOption;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\LiveSessionResponse;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_duplicate_a_published_quiz_as_an_independent_draft(): void
    {
        $educator = User::factory()->educator()->create();
        $source = Quiz::factory()->published()->for($educator, 'educator')->create([
            'title' => 'Algebra readiness',
            'certificates_enabled' => true,
            'shuffle_questions' => true,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDay(),
        ]);
        $question = Question::factory()->for($source)->create([
            'prompt' => 'What is two plus two?',
            'points' => 4,
        ]);
        AnswerOption::factory()->for($question)->correct()->create([
            'content' => 'Four',
            'position' => 1,
        ]);
        AnswerOption::factory()->for($question)->create([
            'content' => 'Five',
            'position' => 2,
        ]);

        $response = $this->actingAs($educator)
            ->post(route('educator.quizzes.duplicate', $source));

        $duplicate = Quiz::query()->where('title', 'Algebra readiness (copy)')->sole();
        $response->assertRedirect(route('educator.quizzes.show', $duplicate))
            ->assertSessionHas('status', 'Editable quiz copy created.');
        $this->assertSame($educator->getKey(), $duplicate->educator_id);
        $this->assertSame(QuizStatus::Draft, $duplicate->status);
        $this->assertNull($duplicate->opens_at);
        $this->assertNull($duplicate->closes_at);
        $this->assertTrue($duplicate->certificates_enabled);
        $this->assertTrue($duplicate->shuffle_questions);
        $this->assertDatabaseHas('questions', [
            'quiz_id' => $duplicate->getKey(),
            'prompt' => 'What is two plus two?',
            'points' => 4,
        ]);
        $copiedQuestion = $duplicate->questions()->sole();
        $this->assertDatabaseHas('answer_options', [
            'question_id' => $copiedQuestion->getKey(),
            'content' => 'Four',
            'is_correct' => true,
        ]);
        $this->assertSame(2, $copiedQuestion->answerOptions()->count());
        $this->assertSame(QuizStatus::Published, $source->fresh()?->status);
    }

    public function test_other_educator_cannot_duplicate_or_archive_a_quiz(): void
    {
        $owner = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($owner, 'educator')->create();

        $this->actingAs($otherEducator)
            ->post(route('educator.quizzes.duplicate', $quiz))
            ->assertForbidden();
        $this->actingAs($otherEducator)
            ->post(route('educator.quizzes.archive', $quiz))
            ->assertForbidden();

        $this->assertDatabaseCount('quizzes', 1);
        $this->assertSame(QuizStatus::Published, $quiz->fresh()?->status);
    }

    public function test_archiving_a_published_quiz_removes_discovery_and_preserves_results(): void
    {
        $educator = User::factory()->educator()->create();
        $learner = User::factory()->learner()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create([
            'title' => 'Retired assessment',
        ]);
        $attempt = QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create();

        $response = $this->actingAs($educator)
            ->post(route('educator.quizzes.archive', $quiz));

        $response->assertRedirect(route('educator.quizzes.show', $quiz))
            ->assertSessionHas('status', 'Quiz archived. Existing results remain available.');
        $this->assertSame(QuizStatus::Archived, $quiz->fresh()?->status);
        $this->assertModelExists($attempt);
        $this->actingAs($educator)
            ->get(route('educator.quizzes.results.index', $quiz))
            ->assertOk();
        $this->actingAs($learner)
            ->get(route('learner.quizzes.index'))
            ->assertDontSee('Retired assessment');
    }

    public function test_archiving_an_archived_quiz_is_forbidden(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->archived()->for($educator, 'educator')->create();

        $this->actingAs($educator)
            ->post(route('educator.quizzes.archive', $quiz))
            ->assertForbidden();

        $this->assertSame(QuizStatus::Archived, $quiz->fresh()?->status);
    }

    public function test_closing_a_published_quiz_stops_new_attempts_and_keeps_existing_work(): void
    {
        $educator = User::factory()->educator()->create();
        $learner = User::factory()->learner()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create([
            'title' => 'Closing assessment',
        ]);
        $attempt = QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create();

        $response = $this->actingAs($educator)
            ->post(route('educator.quizzes.close', $quiz));

        $response->assertRedirect(route('educator.quizzes.show', $quiz))
            ->assertSessionHas('status', 'Quiz closed to new attempts. Existing attempts can still be finished.');
        $this->assertSame(QuizStatus::Closed, $quiz->fresh()?->status);
        $this->assertNotNull($quiz->fresh()?->closed_at);
        $this->assertModelExists($attempt);
        $this->actingAs($learner)
            ->get(route('learner.quizzes.index'))
            ->assertDontSee('Closing assessment');
        $this->actingAs($learner)
            ->post(route('learner.quizzes.attempts.store', $quiz))
            ->assertForbidden();
        $this->actingAs($learner)
            ->get(route('learner.attempts.show', $attempt))
            ->assertOk();
    }

    public function test_other_educator_cannot_close_a_quiz(): void
    {
        $owner = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($owner, 'educator')->create();

        $this->actingAs($otherEducator)
            ->post(route('educator.quizzes.close', $quiz))
            ->assertForbidden();

        $this->assertSame(QuizStatus::Published, $quiz->fresh()?->status);
    }

    public function test_quiz_page_links_to_retained_completed_live_session(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $session = LiveSession::factory()->completed()->for($quiz)->for($educator, 'host')->create();

        $this->actingAs($educator)
            ->get(route('educator.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('Recent sessions')
            ->assertSee(route('educator.live-sessions.show', $session), false);
    }

    public function test_completed_live_session_retains_a_question_report(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $question = Question::factory()->for($quiz)->create([
            'prompt' => 'Which answer is right?',
        ]);
        $correctOption = AnswerOption::factory()->correct()->for($question)->create([
            'content' => 'Right choice',
            'position' => 1,
        ]);
        $incorrectOption = AnswerOption::factory()->for($question)->create([
            'content' => 'Wrong choice',
            'position' => 2,
        ]);
        $session = LiveSession::factory()->completed()->for($quiz)->for($educator, 'host')->create();
        $firstParticipant = LiveSessionParticipant::factory()->for($session, 'liveSession')->create();
        $secondParticipant = LiveSessionParticipant::factory()->for($session, 'liveSession')->create();
        LiveSessionResponse::factory()->create([
            'live_session_participant_id' => $firstParticipant->getKey(),
            'question_id' => $question->getKey(),
            'answer_option_id' => $correctOption->getKey(),
            'is_correct' => true,
            'points_awarded' => 1,
        ]);
        LiveSessionResponse::factory()->create([
            'live_session_participant_id' => $secondParticipant->getKey(),
            'question_id' => $question->getKey(),
            'answer_option_id' => $incorrectOption->getKey(),
            'is_correct' => false,
            'points_awarded' => 0,
        ]);

        $this->actingAs($educator)
            ->get(route('educator.live-sessions.show', $session))
            ->assertOk()
            ->assertSee('Question report')
            ->assertSee('Which answer is right?')
            ->assertSee('1 of 2 correct')
            ->assertSee('Right choice')
            ->assertSee('Wrong choice');
    }

    public function test_active_live_session_shows_csp_safe_distribution(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $question = Question::factory()->for($quiz)->create();
        AnswerOption::factory()->correct()->for($question)->create([
            'content' => 'Correct answer',
        ]);
        $session = LiveSession::factory()->active()->for($quiz)->for($educator, 'host')->create([
            'current_question_id' => $question->getKey(),
            'current_question_started_at' => now(),
        ]);

        $this->actingAs($educator)
            ->get(route('educator.live-sessions.show', $session))
            ->assertOk()
            ->assertSee('<progress', false)
            ->assertDontSee('style=', false);
    }
}
