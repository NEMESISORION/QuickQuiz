<?php

namespace Tests\Feature;

use App\Enums\LiveSessionStatus;
use App\Models\AnswerOption;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\LiveSessionResponse;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LiveSessionGameplayTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_host_starts_with_the_first_question_when_a_learner_is_ready(): void
    {
        [$host, $liveSession, $firstQuestion] = $this->waitingSession();
        LiveSessionParticipant::factory()->for($liveSession)->create();
        $this->freezeTime();

        $response = $this->actingAs($host)->post(route('educator.live-sessions.start', $liveSession));

        $response->assertRedirect()->assertSessionHas('status', 'The first question is live.');
        $liveSession->refresh();
        $this->assertSame(LiveSessionStatus::Active, $liveSession->status);
        $this->assertSame($firstQuestion->getKey(), $liveSession->current_question_id);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $liveSession->started_at?->format('Y-m-d H:i:s'));
    }

    public function test_host_cannot_start_an_empty_lobby(): void
    {
        [$host, $liveSession] = $this->waitingSession();

        $this->actingAs($host)
            ->post(route('educator.live-sessions.start', $liveSession))
            ->assertSessionHasErrors([
                'session' => 'At least one learner must join before the session starts.',
            ]);

        $this->assertSame(LiveSessionStatus::Waiting, $liveSession->fresh()?->status);
    }

    public function test_other_educator_cannot_control_the_session(): void
    {
        [, $liveSession] = $this->waitingSession();
        $otherEducator = User::factory()->educator()->create();

        $this->actingAs($otherEducator)
            ->post(route('educator.live-sessions.start', $liveSession))
            ->assertForbidden();
    }

    public function test_host_advances_questions_then_completes_the_session(): void
    {
        [$host, $liveSession, $firstQuestion, $secondQuestion] = $this->activeSession();

        $this->actingAs($host)->post(route('educator.live-sessions.advance', $liveSession))
            ->assertSessionHas('status', 'The next question is live.');
        $this->assertSame($secondQuestion->getKey(), $liveSession->fresh()?->current_question_id);

        $this->actingAs($host)->post(route('educator.live-sessions.advance', $liveSession))
            ->assertSessionHas('status', 'Live session completed. The final leaderboard is ready.');
        $liveSession->refresh();
        $this->assertSame(LiveSessionStatus::Completed, $liveSession->status);
        $this->assertNull($liveSession->current_question_id);
        $this->assertNotNull($liveSession->ended_at);
        $this->assertSame($firstQuestion->quiz_id, $secondQuestion->quiz_id);
    }

    public function test_correct_answer_is_locked_and_awards_question_points(): void
    {
        [, $liveSession, $question] = $this->activeSession();
        $learner = User::factory()->learner()->create();
        $participant = LiveSessionParticipant::factory()->for($liveSession)->for($learner, 'learner')->create();
        $correctOption = AnswerOption::factory()->correct()->for($question)->create();

        $response = $this->actingAs($learner)->post(
            route('learner.live-sessions.answer', $liveSession),
            ['answer_option_id' => $correctOption->getKey()],
        );

        $response->assertRedirect()->assertSessionHas('status', 'Answer locked. Waiting for the host.');
        $liveResponse = LiveSessionResponse::query()->sole();
        $this->assertSame($participant->getKey(), $liveResponse->live_session_participant_id);
        $this->assertTrue($liveResponse->is_correct);
        $this->assertSame($question->points, $liveResponse->points_awarded);
    }

    public function test_wrong_answer_awards_zero_points(): void
    {
        [, $liveSession, $question] = $this->activeSession();
        $learner = User::factory()->learner()->create();
        LiveSessionParticipant::factory()->for($liveSession)->for($learner, 'learner')->create();
        $wrongOption = AnswerOption::factory()->for($question)->create(['is_correct' => false]);

        $this->actingAs($learner)->post(
            route('learner.live-sessions.answer', $liveSession),
            ['answer_option_id' => $wrongOption->getKey()],
        );

        $this->assertSame(0, LiveSessionResponse::query()->sole()->points_awarded);
    }

    public function test_learner_cannot_submit_an_option_from_another_question(): void
    {
        [, $liveSession, , $otherQuestion] = $this->activeSession();
        $learner = User::factory()->learner()->create();
        LiveSessionParticipant::factory()->for($liveSession)->for($learner, 'learner')->create();
        $staleOption = AnswerOption::factory()->for($otherQuestion)->create();

        $this->actingAs($learner)
            ->post(route('learner.live-sessions.answer', $liveSession), [
                'answer_option_id' => $staleOption->getKey(),
            ])
            ->assertSessionHasErrors([
                'answer_option_id' => 'Choose an answer from the active question.',
            ]);

        $this->assertSame(0, LiveSessionResponse::query()->count());
    }

    public function test_learner_cannot_replace_a_locked_answer(): void
    {
        [, $liveSession, $question] = $this->activeSession();
        $learner = User::factory()->learner()->create();
        $participant = LiveSessionParticipant::factory()->for($liveSession)->for($learner, 'learner')->create();
        $firstOption = AnswerOption::factory()->for($question)->create(['position' => 1]);
        $secondOption = AnswerOption::factory()->correct()->for($question)->create(['position' => 2]);
        LiveSessionResponse::factory()->for($participant, 'participant')->for($question)->for($firstOption, 'answerOption')->create();

        $this->actingAs($learner)
            ->post(route('learner.live-sessions.answer', $liveSession), [
                'answer_option_id' => $secondOption->getKey(),
            ])
            ->assertSessionHasErrors([
                'answer_option_id' => 'Your answer for this question is already locked.',
            ]);

        $this->assertSame($firstOption->getKey(), LiveSessionResponse::query()->sole()->answer_option_id);
    }

    public function test_nonparticipant_cannot_read_session_state_or_submit_answers(): void
    {
        [, $liveSession, $question] = $this->activeSession();
        $outsider = User::factory()->learner()->create();
        $option = AnswerOption::factory()->for($question)->create();

        $this->actingAs($outsider)
            ->getJson(route('live-sessions.state', $liveSession))
            ->assertForbidden();
        $this->actingAs($outsider)
            ->post(route('learner.live-sessions.answer', $liveSession), ['answer_option_id' => $option->getKey()])
            ->assertForbidden();
    }

    public function test_participant_state_version_changes_after_an_answer(): void
    {
        [, $liveSession, $question] = $this->activeSession();
        $learner = User::factory()->learner()->create();
        LiveSessionParticipant::factory()->for($liveSession)->for($learner, 'learner')->create();
        $option = AnswerOption::factory()->for($question)->create();

        $before = $this->actingAs($learner)->getJson(route('live-sessions.state', $liveSession))->json('version');
        $this->actingAs($learner)->post(
            route('learner.live-sessions.answer', $liveSession),
            ['answer_option_id' => $option->getKey()],
        );
        $after = $this->actingAs($learner)->getJson(route('live-sessions.state', $liveSession))->json('version');

        $this->assertNotSame($before, $after);
    }

    public function test_completed_session_retains_and_renders_the_final_leaderboard(): void
    {
        [$host, $liveSession, $question] = $this->activeSession();
        $firstLearner = User::factory()->learner()->create(['name' => 'First Learner']);
        $secondLearner = User::factory()->learner()->create(['name' => 'Second Learner']);
        $firstParticipant = LiveSessionParticipant::factory()->for($liveSession)->for($firstLearner, 'learner')->create();
        $secondParticipant = LiveSessionParticipant::factory()->for($liveSession)->for($secondLearner, 'learner')->create();
        $option = AnswerOption::factory()->correct()->for($question)->create();
        LiveSessionResponse::factory()->for($firstParticipant, 'participant')->for($question)->for($option, 'answerOption')->create([
            'is_correct' => true,
            'points_awarded' => 3,
        ]);
        LiveSessionResponse::factory()->for($secondParticipant, 'participant')->for($question)->for($option, 'answerOption')->create([
            'is_correct' => false,
            'points_awarded' => 0,
        ]);
        $liveSession->update([
            'status' => LiveSessionStatus::Completed,
            'current_question_id' => null,
            'current_question_started_at' => null,
            'ended_at' => now(),
        ]);

        $response = $this->actingAs($host)->get(route('educator.live-sessions.show', $liveSession));

        $response->assertSeeInOrder(['Final standings', 'First Learner', 'Second Learner'])
            ->assertSee('3 / 5');
    }

    /** @return array{User, LiveSession, Question, Question} */
    private function waitingSession(): array
    {
        $host = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($host, 'educator')->create();
        $firstQuestion = Question::factory()->for($quiz)->create(['position' => 1, 'points' => 3]);
        $secondQuestion = Question::factory()->for($quiz)->create(['position' => 2, 'points' => 2]);
        $liveSession = LiveSession::factory()->for($quiz)->for($host, 'host')->create();

        return [$host, $liveSession, $firstQuestion, $secondQuestion];
    }

    /** @return array{User, LiveSession, Question, Question} */
    private function activeSession(): array
    {
        [$host, $liveSession, $firstQuestion, $secondQuestion] = $this->waitingSession();
        $liveSession->update([
            'status' => LiveSessionStatus::Active,
            'current_question_id' => $firstQuestion->getKey(),
            'current_question_started_at' => now(),
            'started_at' => now(),
        ]);

        return [$host, $liveSession->refresh(), $firstQuestion, $secondQuestion];
    }
}
