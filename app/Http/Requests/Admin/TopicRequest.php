<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TopicRequest extends FormRequest
{
    /**
     * Access is already limited to admins by the 'role:admin' route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'slug' => Str::slug((string) $this->input('name')),
        ]);
    }

    /**
     * Topic slugs are unique per subject (matches the DB unique(subject_id, slug)),
     * so "Basics" can exist under both Physics and Chemistry.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', Rule::exists(Subject::class, 'id')],
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:140',
                Rule::unique(Topic::class)
                    ->where('subject_id', $this->integer('subject_id'))
                    ->ignore($this->route('topic')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'The name must contain at least one letter or number.',
            'slug.unique' => 'This subject already has a topic with this name.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['subject_id' => 'subject'];
    }
}
