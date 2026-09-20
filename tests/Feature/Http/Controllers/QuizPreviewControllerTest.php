<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizPreviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_sees_escaped_learner_preview_without_correct_answer_disclosure(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();
        $question = Question::factory()->for($quiz)->create(['prompt' => '<script>unsafe()</script>']);
        AnswerOption::factory()->correct()->for($question)->create(['content' => 'Secret answer']);

        $response = $this->actingAs($educator)->get(route('educator.quizzes.preview', $quiz));

        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>unsafe()</script>', false)
            ->assertSee('Secret answer')
            ->assertDontSee('Correct answer');
    }

    public function test_other_educator_cannot_preview_quiz(): void
    {
        $owner = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($owner, 'educator')->create();

        $this->actingAs($otherEducator)
            ->get(route('educator.quizzes.preview', $quiz))
            ->assertForbidden();
    }
}
