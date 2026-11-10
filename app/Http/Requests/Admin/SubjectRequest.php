<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Used for both store and update. On update, the current subject is ignored
 * by the unique rules (route model binding gives us $this->route('subject')).
 */
class SubjectRequest extends FormRequest
{
    /**
     * Access is already limited to admins by the 'role:admin' route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The slug is derived from the name, never typed by the user. It is still
     * validated, because two different names can produce the same slug
     * ("C++" and "C" both become "c").
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug((string) $this->input('name')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $subject = $this->route('subject');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Subject::class)->ignore($subject)],
            'slug' => ['required', 'string', 'max:120', Rule::unique(Subject::class)->ignore($subject)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'The name must contain at least one letter or number.',
            'slug.unique' => 'A subject with a very similar name already exists.',
        ];
    }
}
