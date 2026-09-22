<?php

namespace App\Http\Requests;

use App\Enums\QuizReviewPolicy;
use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz && ($this->user()?->can('update', $quiz) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'pass_percentage' => ['required', 'integer', 'between:1,100'],
            'max_attempts' => ['required', 'integer', 'between:1,100'],
            'shuffle_questions' => ['required', 'boolean'],
            'shuffle_answers' => ['required', 'boolean'],
            'review_policy' => ['required', Rule::enum(QuizReviewPolicy::class)],
            'certificates_enabled' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->string('title')->trim()->toString(),
            'description' => $this->filled('description')
                ? $this->string('description')->trim()->toString()
                : null,
            'shuffle_questions' => $this->boolean('shuffle_questions'),
            'shuffle_answers' => $this->boolean('shuffle_answers'),
            'certificates_enabled' => $this->boolean('certificates_enabled'),
        ]);
    }
}
