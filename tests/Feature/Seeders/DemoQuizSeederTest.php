<?php

namespace Tests\Feature\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizStatus;
use App\Models\Question;
use App\Models\Quiz;
use Database\Seeders\DemoQuizSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use LogicException;
use Tests\TestCase;

class DemoQuizSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_ordered_demo_quiz_for_demo_educator(): void
    {
        $this->seed(DemoUserSeeder::class);

        $this->seed(DemoQuizSeeder::class);

        $quiz = Quiz::query()->with('questions.answerOptions')->sole();
        $lastQuestion = $quiz->questions->last();

        $this->assertInstanceOf(Question::class, $lastQuestion);
        $this->assertSame('educator@demo.quickquiz.test', $quiz->educator->email);
        $this->assertSame(QuizStatus::Draft, $quiz->status);
        $this->assertSame([1, 2], $quiz->questions->pluck('position')->all());
        $this->assertSame(QuestionType::TrueFalse, $lastQuestion->type);
        $this->assertSame(
            ['True', 'False'],
            $lastQuestion->answerOptions->pluck('content')->all(),
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DemoUserSeeder::class);

        $this->seed(DemoQuizSeeder::class);
        $this->seed(DemoQuizSeeder::class);

        $this->assertDatabaseCount('quizzes', 1);
        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('answer_options', 6);
    }

    public function test_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Demo quizzes cannot be seeded in production.');

        (new DemoQuizSeeder)->setContainer($this->app)->run();
    }
}
