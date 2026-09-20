<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizReviewPolicy;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DemoQuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new LogicException('Demo quizzes cannot be seeded in production.');
        }

        $educator = User::query()
            ->where('email', 'educator@demo.quickquiz.test')
            ->firstOrFail();

        $quiz = $educator->quizzes()->updateOrCreate(
            [
                'title' => 'Web Foundations Checkpoint',
            ],
            [
                'description' => 'A short demo assessment covering web and Laravel fundamentals.',
                'status' => QuizStatus::Draft,
                'duration_minutes' => 10,
                'pass_percentage' => 70,
                'max_attempts' => 2,
                'shuffle_questions' => false,
                'shuffle_answers' => false,
                'review_policy' => QuizReviewPolicy::Immediately,
                'opens_at' => null,
                'closes_at' => null,
                'published_at' => null,
                'closed_at' => null,
            ],
        );

        $this->upsertQuestion(
            quiz: $quiz,
            position: 1,
            type: QuestionType::MultipleChoice,
            prompt: 'Which HTTP method should retrieve a resource without changing it?',
            explanation: 'GET requests are intended to retrieve resource representations safely.',
            options: [
                ['content' => 'GET', 'is_correct' => true],
                ['content' => 'POST', 'is_correct' => false],
                ['content' => 'PATCH', 'is_correct' => false],
                ['content' => 'DELETE', 'is_correct' => false],
            ],
        );

        $this->upsertQuestion(
            quiz: $quiz,
            position: 2,
            type: QuestionType::TrueFalse,
            prompt: 'Laravel web forms should include CSRF protection.',
            explanation: 'Laravel validates a CSRF token for state-changing web requests.',
            options: [
                ['content' => 'True', 'is_correct' => true],
                ['content' => 'False', 'is_correct' => false],
            ],
        );

        $quiz->questions()->whereNotIn('position', [1, 2])->delete();
    }

    /**
     * @param  list<array{content: string, is_correct: bool}>  $options
     */
    private function upsertQuestion(
        Quiz $quiz,
        int $position,
        QuestionType $type,
        string $prompt,
        string $explanation,
        array $options,
    ): void {
        $question = $quiz->questions()->updateOrCreate(
            ['position' => $position],
            [
                'type' => $type,
                'prompt' => $prompt,
                'explanation' => $explanation,
                'points' => 1,
            ],
        );

        foreach ($options as $index => $option) {
            $question->answerOptions()->updateOrCreate(
                ['position' => $index + 1],
                $option,
            );
        }

        $question->answerOptions()->where('position', '>', count($options))->delete();
    }
}
