<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Quiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EducatorAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Educator) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $educatorId = $this->user()?->getAuthIdentifier();

        return [
            'quiz_id' => [
                'nullable',
                'integer',
                Rule::exists(Quiz::class, 'id')->where('educator_id', $educatorId),
            ],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $from = $this->date('from');
            $to = $this->date('to');

            if ($from !== null && $to !== null && $from->diffInDays($to) > 366) {
                $validator->errors()->add('to', 'Choose a date range of one year or less.');
            }
        }];
    }
}
