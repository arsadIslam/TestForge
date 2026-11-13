<?php

namespace App\Http\Requests\Teacher;

use App\Support\QuestionRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * The rules themselves live in App\Support\QuestionRules, shared with the CSV importer.
 */
class StoreQuestionRequest extends FormRequest
{
    public const MIN_OPTIONS = QuestionRules::MIN_OPTIONS;

    public const MAX_OPTIONS = QuestionRules::MAX_OPTIONS;

    /**
     * Authorization is done by QuestionController's 'can:' middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * - tags arrive as one comma-separated string ("Tricky, Important") and are split
     *   into tag_names, de-duplicated by slug so "Tricky" and "tricky" count once;
     * - an unticked checkbox sends nothing, so is_active is normalised to a real boolean.
     */
    protected function prepareForValidation(): void
    {
        $tagNames = collect(explode(',', (string) $this->input('tags')))
            ->map(fn (string $name): string => Str::squish($name))
            ->filter(fn (string $name): bool => Str::slug($name) !== '')
            ->unique(fn (string $name): string => Str::slug($name))
            ->values()
            ->all();

        $this->merge([
            'tag_names' => $tagNames,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...QuestionRules::question(),
            ...($this->hasOptionRules() ? QuestionRules::options() : []),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // all(), not input(): the option images are files, and input() leaves files out.
                $options = $this->all()['options'] ?? null;

                if (! $this->hasOptionRules() || ! is_array($options) || $validator->errors()->has('type')) {
                    return;
                }

                QuestionRules::checkOptions($validator, $this->input('type'), $options, $this->existingOptionImages());
            },
        ];
    }

    protected function hasOptionRules(): bool
    {
        return true;
    }

    /**
     * Option id => it already has an image. A brand-new question has none.
     *
     * @return array<int, bool>
     */
    protected function existingOptionImages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return QuestionRules::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return QuestionRules::attributes();
    }
}
