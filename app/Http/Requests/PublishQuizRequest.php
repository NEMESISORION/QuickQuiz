<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PublishQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz && ($this->user()?->can('publish', $quiz) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['immediate', 'scheduled'])],
            'opens_at' => ['nullable', 'required_if:mode,scheduled', 'date', 'after:now'],
            'closes_at' => [
                'nullable',
                'date',
                Rule::when($this->input('mode') === 'scheduled', ['after:opens_at'], ['after:now']),
            ],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $quiz = $this->route('quiz');

            if (! $quiz instanceof Quiz || $validator->errors()->isNotEmpty()) {
                return;
            }

            $questions = $quiz->questions()->with('answerOptions')->get();

            if ($questions->isEmpty()) {
                $validator->errors()->add('quiz', 'Add at least one question before publishing.');

                return;
            }

            foreach ($questions as $question) {
                if ($question->answerOptions->count() < 2 || $question->answerOptions->where('is_correct', true)->count() !== 1) {
                    $validator->errors()->add('quiz', 'Every question needs at least two options and exactly one correct answer.');

                    return;
                }
            }
        }];
    }
}
