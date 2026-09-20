<?php

namespace App\Http\Requests;

use App\Models\Question;

class UpdateQuestionRequest extends StoreQuestionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $question instanceof Question
            && ($this->user()?->can('update', $question) ?? false);
    }
}
