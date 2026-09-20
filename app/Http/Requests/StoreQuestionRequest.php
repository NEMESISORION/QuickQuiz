<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz
            && ($this->user()?->can('create', [Question::class, $quiz]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['required', 'integer', 'between:1,100'],
            'options' => ['required', 'array', 'size:4'],
            'options.*.content' => ['nullable', 'string', 'max:1000'],
            'correct_option' => ['required', 'integer', 'between:0,3'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['type', 'options', 'correct_option'])) {
                return;
            }

            $type = QuestionType::tryFrom($this->string('type')->toString());
            $correctOption = $this->integer('correct_option');

            if ($type === QuestionType::TrueFalse) {
                if (! in_array($correctOption, [0, 1], true)) {
                    $validator->errors()->add('correct_option', 'Choose True or False as the correct answer.');
                }

                return;
            }

            $optionInput = $this->input('options', []);
            $options = [];

            foreach (is_array($optionInput) ? $optionInput : [] as $option) {
                $options[] = trim((string) data_get($option, 'content'));
            }

            $filledOptions = array_values(array_filter($options));

            if (count($filledOptions) < 2) {
                $validator->errors()->add('options', 'Multiple-choice questions need at least two answer options.');
            }

            if (! filled($options[$correctOption] ?? null)) {
                $validator->errors()->add('correct_option', 'Choose a filled answer option as the correct answer.');
            }

            $normalizedOptions = array_map(fn (string $option): string => Str::lower($option), $filledOptions);

            if (count(array_unique($normalizedOptions)) !== count($filledOptions)) {
                $validator->errors()->add('options', 'Answer options must be unique.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prompt' => $this->string('prompt')->trim()->toString(),
            'explanation' => $this->filled('explanation')
                ? $this->string('explanation')->trim()->toString()
                : null,
        ]);
    }
}
