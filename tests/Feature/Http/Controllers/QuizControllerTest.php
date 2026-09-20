<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\QuizReviewPolicy;
use App\Enums\QuizStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_quiz_library_to_login(): void
    {
        $this->get(route('educator.quizzes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_learner_is_forbidden_from_quiz_management(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)
            ->get(route('educator.quizzes.index'))
            ->assertForbidden();
    }

    public function test_library_displays_only_educators_quizzes_and_escapes_content(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create([
            'title' => '<script>alert("owned")</script>',
        ]);
        Quiz::factory()->for($otherEducator, 'educator')->create([
            'title' => 'Another educator quiz',
        ]);
        Question::factory()->for($quiz)->create();

        $response = $this->actingAs($educator)
            ->get(route('educator.quizzes.index'));

        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("owned")</script>', false)
            ->assertSee('1 question')
            ->assertDontSee('Another educator quiz');
    }

    public function test_educator_can_open_create_form(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();

        $this->actingAs($educator)
            ->get(route('educator.quizzes.create'))
            ->assertOk()
            ->assertSee('Create a focused quiz')
            ->assertSee('Quiz title');
    }

    public function test_valid_payload_creates_owned_draft_and_ignores_protected_fields(): void
    {
        $educator = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();

        $response = $this->actingAs($educator)
            ->post(route('educator.quizzes.store'), [
                ...$this->validPayload(),
                'title' => '  Accessible web foundations  ',
                'educator_id' => $otherEducator->id,
                'status' => QuizStatus::Published->value,
            ]);

        $quiz = Quiz::query()->sole();
        $response->assertRedirect(route('educator.quizzes.show', $quiz))
            ->assertSessionHas('status', 'Quiz draft created.');
        $this->assertSame($educator->id, $quiz->educator_id);
        $this->assertSame('Accessible web foundations', $quiz->title);
        $this->assertSame(QuizStatus::Draft, $quiz->status);
        $this->assertSame(QuizReviewPolicy::AfterClose, $quiz->review_policy);
        $this->assertTrue($quiz->shuffle_questions);
        $this->assertFalse($quiz->shuffle_answers);
    }

    public function test_invalid_payload_returns_specific_errors_without_creating_quiz(): void
    {
        $educator = User::factory()->educator()->create();

        $response = $this->actingAs($educator)
            ->from(route('educator.quizzes.create'))
            ->post(route('educator.quizzes.store'), [
                'title' => '   ',
                'description' => str_repeat('a', 5001),
                'duration_minutes' => 0,
                'pass_percentage' => 101,
                'max_attempts' => 0,
                'review_policy' => 'reveal_everything',
            ]);

        $response->assertRedirect(route('educator.quizzes.create'))
            ->assertSessionHasErrors([
                'title' => 'The title field is required.',
                'description' => 'The description field must not be greater than 5000 characters.',
                'duration_minutes' => 'The duration minutes field must be at least 1.',
                'pass_percentage' => 'The pass percentage field must be between 1 and 100.',
                'max_attempts' => 'The max attempts field must be between 1 and 100.',
                'review_policy' => 'The selected review policy is invalid.',
            ]);
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_educator_can_view_owned_quiz_with_question_summary(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create([
            'title' => 'Security foundations',
        ]);
        Question::factory()->for($quiz)->create([
            'prompt' => 'What protects a state-changing form?',
            'points' => 3,
        ]);

        $this->actingAs($educator)
            ->get(route('educator.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('Security foundations')
            ->assertSee('What protects a state-changing form?')
            ->assertSee('3 pts');
    }

    public function test_other_educator_is_forbidden_from_viewing_or_updating_quiz(): void
    {
        $owner = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($owner, 'educator')->create();

        $this->actingAs($otherEducator)
            ->get(route('educator.quizzes.show', $quiz))
            ->assertForbidden();
        $this->actingAs($otherEducator)
            ->patch(route('educator.quizzes.update', $quiz), $this->validPayload())
            ->assertForbidden();
    }

    public function test_owner_can_update_draft_without_changing_status_or_owner(): void
    {
        $educator = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();

        $response = $this->actingAs($educator)
            ->patch(route('educator.quizzes.update', $quiz), [
                ...$this->validPayload(),
                'title' => 'Updated assessment',
                'educator_id' => $otherEducator->id,
                'status' => QuizStatus::Archived->value,
            ]);

        $response->assertRedirect(route('educator.quizzes.show', $quiz))
            ->assertSessionHas('status', 'Quiz settings updated.');
        $quiz->refresh();
        $this->assertSame('Updated assessment', $quiz->title);
        $this->assertSame($educator->id, $quiz->educator_id);
        $this->assertSame(QuizStatus::Draft, $quiz->status);
    }

    public function test_published_quiz_cannot_be_edited(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();

        $this->actingAs($educator)
            ->patch(route('educator.quizzes.update', $quiz), $this->validPayload())
            ->assertForbidden();

        $this->assertNotSame('Assessment readiness check', $quiz->fresh()->title);
    }

    public function test_owner_can_soft_delete_draft_but_not_published_quiz(): void
    {
        $educator = User::factory()->educator()->create();
        $draft = Quiz::factory()->for($educator, 'educator')->create();
        $published = Quiz::factory()->published()->for($educator, 'educator')->create();

        $this->actingAs($educator)
            ->delete(route('educator.quizzes.destroy', $draft))
            ->assertRedirect(route('educator.quizzes.index'))
            ->assertSessionHas('status', 'Quiz moved to the archive.');
        $this->assertSoftDeleted($draft);

        $this->actingAs($educator)
            ->delete(route('educator.quizzes.destroy', $published))
            ->assertForbidden();
        $this->assertNotSoftDeleted($published);
    }

    /**
     * @return array<string, int|string|bool|null>
     */
    private function validPayload(): array
    {
        return [
            'title' => 'Assessment readiness check',
            'description' => 'Checks the skills required before the next learning module.',
            'duration_minutes' => 25,
            'pass_percentage' => 75,
            'max_attempts' => 2,
            'shuffle_questions' => true,
            'shuffle_answers' => false,
            'review_policy' => QuizReviewPolicy::AfterClose->value,
        ];
    }
}
