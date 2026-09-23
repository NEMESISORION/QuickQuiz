<?php

namespace App\Domain\Authoring;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateQuiz
{
    public function handle(Quiz $source, User $educator): Quiz
    {
        return DB::transaction(function () use ($source, $educator): Quiz {
            $duplicate = $educator->quizzes()->create([
                'title' => Str::limit($source->title, 153, '').' (copy)',
                'description' => $source->description,
                'status' => QuizStatus::Draft,
                'duration_minutes' => $source->duration_minutes,
                'pass_percentage' => $source->pass_percentage,
                'max_attempts' => $source->max_attempts,
                'shuffle_questions' => $source->shuffle_questions,
                'shuffle_answers' => $source->shuffle_answers,
                'review_policy' => $source->review_policy,
                'certificates_enabled' => $source->certificates_enabled,
            ]);

            foreach ($source->questions()->with('answerOptions')->get() as $question) {
                $newQuestion = $duplicate->questions()->create([
                    'type' => $question->type,
                    'prompt' => $question->prompt,
                    'explanation' => $question->explanation,
                    'points' => $question->points,
                    'position' => $question->position,
                ]);

                foreach ($question->answerOptions as $option) {
                    $newQuestion->answerOptions()->create([
                        'content' => $option->content,
                        'is_correct' => $option->is_correct,
                        'position' => $option->position,
                    ]);
                }
            }

            return $duplicate;
        }, 3);
    }
}
