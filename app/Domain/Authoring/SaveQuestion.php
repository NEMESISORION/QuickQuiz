<?php

namespace App\Domain\Authoring;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveQuestion
{
    /**
     * @param  array{type: string, prompt: string, explanation: ?string, points: int|string, options: list<array{content?: ?string}>, correct_option: int|string}  $data
     */
    public function handle(Quiz $quiz, array $data, ?Question $question = null): Question
    {
        return DB::transaction(function () use ($quiz, $data, $question): Question {
            $attributes = Arr::only($data, ['type', 'prompt', 'explanation', 'points']);

            if ($question === null) {
                $attributes['position'] = ((int) $quiz->questions()->max('position')) + 1;
                $question = $quiz->questions()->create($attributes);
            } else {
                $question->update($attributes);
                $question->answerOptions()->delete();
            }

            $type = QuestionType::from($data['type']);
            $correctOption = (int) $data['correct_option'];
            $options = $type === QuestionType::TrueFalse
                ? [['content' => 'True'], ['content' => 'False']]
                : array_filter(
                    $data['options'],
                    fn (array $option): bool => filled($option['content'] ?? null),
                );

            $position = 1;

            foreach ($options as $sourceIndex => $option) {
                $question->answerOptions()->create([
                    'content' => trim((string) $option['content']),
                    'is_correct' => $sourceIndex === $correctOption,
                    'position' => $position++,
                ]);
            }

            return $question->load('answerOptions');
        });
    }
}
