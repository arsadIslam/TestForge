<?php

namespace App\Http\Requests\Teacher;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachQuestionsRequest extends FormRequest
{
    /**
     * Authorization is done by TestQuestionController's 'can:update,test' middleware.
     */
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
            'question_ids' => ['required', 'array', 'min:1', 'max:100'],
            'question_ids.*' => [
                'integer',
                'distinct',
                // only active, non-deleted questions can be added to a test
                Rule::exists(Question::class, 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question_ids.required' => 'Tick at least one question to add.',
            'question_ids.*.exists' => 'One of the selected questions is inactive or no longer exists.',
        ];
    }
}
