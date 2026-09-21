<?php

namespace App\Domain\Attempts;

use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;

class ScoreQuizAttempt
{
    public function handle(QuizAttempt $attempt, QuizAttemptStatus $status): QuizAttempt
    {
        $attempt->loadMissing(['questions', 'answers.option']);
        $questions = $attempt->questions->keyBy('id');

        $score = $attempt->answers->sum(function (QuizAttemptAnswer $answer) use ($questions): int {
            $question = $questions->get($answer->quiz_attempt_question_id);

            if ($question === null
                || $answer->option->quiz_attempt_question_id !== $question->getKey()
                || ! $answer->option->is_correct) {
                return 0;
            }

            return $question->points;
        });

        $attempt->update([
            'status' => $status,
            'submitted_at' => now(),
            'score' => $score,
            'passed' => $score * 100 >= $attempt->max_score * $attempt->pass_percentage_snapshot,
        ]);

        return $attempt;
    }
}
