<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\QuestionType;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuestionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_create_multiple_choice_question_with_one_correct_answer(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();

        $response = $this->actingAs($educator)->post(
            route('educator.quizzes.questions.store', $quiz),
            $this->questionPayload(),
        );

        $question = Question::query()->with('answerOptions')->sole();
        $response->assertRedirect(route('educator.quizzes.show', $quiz));
        $this->assertSame(QuestionType::MultipleChoice, $question->type);
        $this->assertSame(1, $question->position);
        $this->assertSame(['HTTP', 'HTML', 'CSS'], $question->answerOptions->pluck('content')->all());
        $this->assertSame('HTTP', $question->answerOptions->firstWhere('is_correct', true)?->content);
    }

    public function test_true_false_question_uses_canonical_options(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();

        $this->actingAs($educator)->post(route('educator.quizzes.questions.store', $quiz), [
            ...$this->questionPayload(),
            'type' => QuestionType::TrueFalse->value,
            'correct_option' => 1,
        ])->assertRedirect();

        $question = Question::query()->with('answerOptions')->sole();
        $this->assertSame(['True', 'False'], $question->answerOptions->pluck('content')->all());
        $this->assertSame('False', $question->answerOptions->firstWhere('is_correct', true)?->content);
    }

    public function test_multiple_choice_requires_distinct_filled_options_and_valid_correct_choice(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();

        $response = $this->actingAs($educator)->post(route('educator.quizzes.questions.store', $quiz), [
            ...$this->questionPayload(),
            'options' => [
                ['content' => 'Same'],
                ['content' => 'same'],
                ['content' => ''],
                ['content' => ''],
            ],
            'correct_option' => 3,
        ]);

        $response->assertSessionHasErrors([
            'options' => 'Answer options must be unique.',
            'correct_option' => 'Choose a filled answer option as the correct answer.',
        ]);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_nested_binding_hides_question_from_another_quiz(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();
        $otherQuiz = Quiz::factory()->for($educator, 'educator')->create();
        $question = Question::factory()->for($otherQuiz)->create();

        $this->actingAs($educator)
            ->get(route('educator.quizzes.questions.edit', [$quiz, $question]))
            ->assertNotFound();
    }

    public function test_published_quiz_questions_cannot_be_changed(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();

        $this->actingAs($educator)
            ->post(route('educator.quizzes.questions.store', $quiz), $this->questionPayload())
            ->assertForbidden();
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_owner_can_update_reorder_and_delete_draft_questions(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();
        $first = $this->questionWithOptions($quiz, 1, 'First');
        $second = $this->questionWithOptions($quiz, 2, 'Second');

        $this->actingAs($educator)->patch(
            route('educator.quizzes.questions.update', [$quiz, $first]),
            [...$this->questionPayload(), 'prompt' => 'Updated first'],
        )->assertRedirect(route('educator.quizzes.show', $quiz));
        $this->assertSame('Updated first', $first->fresh()?->prompt);

        $this->actingAs($educator)->patch(
            route('educator.quizzes.questions.position', [$quiz, $second]),
            ['direction' => 'up'],
        )->assertRedirect(route('educator.quizzes.show', $quiz));
        $this->assertSame(2, $first->fresh()?->position);
        $this->assertSame(1, $second->fresh()?->position);

        $this->actingAs($educator)
            ->delete(route('educator.quizzes.questions.destroy', [$quiz, $second]))
            ->assertRedirect(route('educator.quizzes.show', $quiz));
        $this->assertModelMissing($second);
        $this->assertSame(1, $first->fresh()?->position);
    }

    /** @return array<string, mixed> */
    private function questionPayload(): array
    {
        return [
            'type' => QuestionType::MultipleChoice->value,
            'prompt' => 'Which protocol transfers web resources?',
            'explanation' => 'HTTP is the web transfer protocol.',
            'points' => 2,
            'options' => [
                ['content' => 'HTTP'],
                ['content' => 'HTML'],
                ['content' => 'CSS'],
                ['content' => ''],
            ],
            'correct_option' => 0,
        ];
    }

    private function questionWithOptions(Quiz $quiz, int $position, string $prompt): Question
    {
        $question = Question::factory()->for($quiz)->create(compact('position', 'prompt'));
        AnswerOption::factory()->correct()->for($question)->create(['position' => 1]);
        AnswerOption::factory()->for($question)->create(['position' => 2]);

        return $question;
    }
}
