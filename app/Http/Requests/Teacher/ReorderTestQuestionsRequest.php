<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the shape is validated here. Whether the ids are exactly the test's current
 * questions is checked by TestService under a row lock, where the answer can't change.
 */
class ReorderTestQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['integer', 'distinct'],
        ];
    }
}
