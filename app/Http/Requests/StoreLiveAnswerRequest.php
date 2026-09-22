<?php

namespace App\Http\Requests;

use App\Models\LiveSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLiveAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $liveSession = $this->route('liveSession');

        return $liveSession instanceof LiveSession
            && ($this->user()?->can('answer', $liveSession) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'answer_option_id' => ['required', 'integer'],
        ];
    }
}
