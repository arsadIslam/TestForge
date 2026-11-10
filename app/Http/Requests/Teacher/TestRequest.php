<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Test details, for both create and update. Rules that depend on the attached
 * questions (pass marks ≤ total marks, at least one question) are checked by
 * TestService::publish(), because questions can still change while it is a draft.
 */
class TestRequest extends FormRequest
{
    /**
     * Authorization is done by TestController's 'can:' middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['shuffle_questions' => $this->boolean('shuffle_questions')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'pass_marks' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', Rule::when($this->filled('starts_at'), ['after:starts_at'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'duration_minutes' => 'duration',
            'max_attempts' => 'attempts allowed',
            'starts_at' => 'start time',
            'ends_at' => 'end time',
        ];
    }
}
