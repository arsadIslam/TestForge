<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 6;

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
            'topic_id' => ['required', 'integer', Rule::exists(Topic::class, 'id')],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'marks' => ['required', 'numeric', 'decimal:0,2', 'min:0.25', 'max:100'],
            'negative_marks' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:marks'],
            'is_active' => ['required', 'boolean'],
            'tags' => ['nullable', 'string', 'max:500'],
            'tag_names' => ['array', 'max:10'],
            'tag_names.*' => ['string', 'max:50'],
            ...$this->optionRules(),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function optionRules(): array
    {
        return [
            'options' => ['required', 'array', 'min:'.self::MIN_OPTIONS, 'max:'.self::MAX_OPTIONS],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.text' => ['required', 'string', 'max:1000', 'distinct:ignore_case'],
            'options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    /**
     * Rules that span several fields: how many options are marked correct depends on the type.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $options = $this->input('options');

                if (! $this->hasOptionRules() || ! is_array($options) || $validator->errors()->has('type')) {
                    return;
                }

                $correct = collect($options)
                    ->filter(fn ($option): bool => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))
                    ->count();

                if ($this->input('type') === QuestionType::Single->value && $correct !== 1) {
                    $validator->errors()->add('options', 'A single-choice question needs exactly one correct option.');
                }

                if ($this->input('type') === QuestionType::Multiple->value && $correct < 1) {
                    $validator->errors()->add('options', 'Mark at least one option as correct.');
                }
            },
        ];
    }

    protected function hasOptionRules(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'options.min' => 'Add at least :min options.',
            'options.max' => 'A question can have at most :max options.',
            'options.*.text.distinct' => 'Two options have the same text.',
            'negative_marks.lte' => 'Negative marks cannot be more than the marks for a correct answer.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'topic_id' => 'topic',
            'options.*.text' => 'option text',
            'tag_names.*' => 'tag',
        ];
    }
}
