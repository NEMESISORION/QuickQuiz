<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\QuizStatus;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizPublicationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_empty_quiz_cannot_be_published(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();

        $response = $this->actingAs($educator)->post(
            route('educator.quizzes.publication.store', $quiz),
            ['mode' => 'immediate'],
        );

        $response->assertSessionHasErrors([
            'quiz' => 'Add at least one question before publishing.',
        ]);
        $this->assertSame(QuizStatus::Draft, $quiz->fresh()?->status);
    }

    public function test_quiz_with_incomplete_answer_key_cannot_be_published(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();
        Question::factory()->for($quiz)->create();

        $this->actingAs($educator)->post(
            route('educator.quizzes.publication.store', $quiz),
            ['mode' => 'immediate'],
        )->assertSessionHasErrors([
            'quiz' => 'Every question needs at least two options and exactly one correct answer.',
        ]);

        $this->assertSame(QuizStatus::Draft, $quiz->fresh()?->status);
    }

    public function test_valid_quiz_can_be_published_immediately(): void
    {
        $this->freezeTime();
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();
        $this->addValidQuestion($quiz);

        $response = $this->actingAs($educator)->post(
            route('educator.quizzes.publication.store', $quiz),
            [
                'mode' => 'immediate',
                'closes_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ],
        );

        $response->assertRedirect(route('educator.quizzes.show', $quiz))
            ->assertSessionHas('status', 'Quiz published successfully.');
        $quiz->refresh();
        $this->assertSame(QuizStatus::Published, $quiz->status);
        $this->assertNotNull($quiz->published_at);
        $this->assertNotNull($quiz->opens_at);
        $this->assertNotNull($quiz->closes_at);
    }

    public function test_valid_quiz_can_be_scheduled_with_bounded_availability(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();
        $this->addValidQuestion($quiz);

        $response = $this->actingAs($educator)->post(
            route('educator.quizzes.publication.store', $quiz),
            [
                'mode' => 'scheduled',
                'opens_at' => '2026-09-21 09:00:00',
                'closes_at' => '2026-09-22 17:00:00',
            ],
        );

        $response->assertRedirect(route('educator.quizzes.show', $quiz))
            ->assertSessionHas('status', 'Quiz scheduled successfully.');
        $quiz->refresh();
        $this->assertSame(QuizStatus::Scheduled, $quiz->status);
        $this->assertSame('2026-09-21 09:00:00', $quiz->opens_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-22 17:00:00', $quiz->closes_at?->format('Y-m-d H:i:s'));
        $this->assertNull($quiz->published_at);
    }

    public function test_other_educator_cannot_publish_quiz(): void
    {
        $owner = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($owner, 'educator')->create();
        $this->addValidQuestion($quiz);

        $this->actingAs($otherEducator)->post(
            route('educator.quizzes.publication.store', $quiz),
            ['mode' => 'immediate'],
        )->assertForbidden();

        $this->assertSame(QuizStatus::Draft, $quiz->fresh()?->status);
    }

    private function addValidQuestion(Quiz $quiz): void
    {
        $question = Question::factory()->for($quiz)->create();
        AnswerOption::factory()->correct()->for($question)->create(['position' => 1]);
        AnswerOption::factory()->for($question)->create(['position' => 2]);
    }
}
