<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Difficulty;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Rules for "Auto-select questions": up to 10 rules of subject · topic? · difficulty? · count.
 */
class GenerateTestQuestionsRequest extends FormRequest
{
    public const MAX_RULES = 10;

    public const MAX_TOTAL = 200;

    public function authorize(): bool
    {
        return true; // 'can:update,test' on the controller
    }

    protected function prepareForValidation(): void
    {
        // An empty <select> means "any": turn '' into null.
        $rules = collect($this->input('rules', []))->map(fn ($rule) => [
            ...(array) $rule,
            'topic_id' => ($rule['topic_id'] ?? '') === '' ? null : $rule['topic_id'],
            'difficulty' => ($rule['difficulty'] ?? '') === '' ? null : $rule['difficulty'],
        ])->values()->all();

        $this->merge(['rules' => $rules]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['add', 'replace'])],
            'rules' => ['required', 'array', 'min:1', 'max:'.self::MAX_RULES],
            'rules.*.subject_id' => ['required', 'integer', Rule::exists(Subject::class, 'id')],
            'rules.*.topic_id' => ['nullable', 'integer', Rule::exists(Topic::class, 'id')],
            'rules.*.difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'rules.*.count' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rules = $this->input('rules');

            if (array_sum(array_column($rules, 'count')) > self::MAX_TOTAL) {
                $validator->errors()->add('rules', 'At most '.self::MAX_TOTAL.' questions can be generated at once.');
            }

            foreach ($rules as $i => $rule) {
                if ($rule['topic_id'] && Topic::whereKey($rule['topic_id'])->value('subject_id') !== (int) $rule['subject_id']) {
                    $validator->errors()->add("rules.{$i}.topic_id", 'Rule '.($i + 1).': that topic is not part of the chosen subject.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rules.*.subject_id' => 'subject',
            'rules.*.topic_id' => 'topic',
            'rules.*.count' => 'number of questions',
        ];
    }
}
