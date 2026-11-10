<?php

namespace App\Http\Requests\Student;

use App\Models\QuestionOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Body of the auto-save fetch: { "option_ids": [12, 14] }  ([] clears the answer).
 * Attempt ownership is checked by the route's 'can:saveAnswer,attempt' middleware;
 * time and status are checked by AttemptService under a row lock.
 */
class SaveAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'option_ids' => ['present', 'array', 'max:10'],
            'option_ids.*' => [
                'integer',
                'distinct',
                // every option must belong to THIS question
                Rule::exists(QuestionOption::class, 'id')->where('question_id', $this->route('question')->id),
            ],
        ];
    }
}
