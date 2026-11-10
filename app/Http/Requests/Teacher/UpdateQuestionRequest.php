<?php

namespace App\Http\Requests\Teacher;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends StoreQuestionRequest
{
    private ?bool $locked = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Locked question: type and options cannot change, so they are not validated
        // (QuestionService ignores them too).
        if ($this->isLocked()) {
            return collect($rules)
                ->reject(fn ($rule, string $field): bool => $field === 'type' || str_starts_with($field, 'options'))
                ->all();
        }

        return $rules;
    }

    /**
     * An option id must belong to the question being edited, so a crafted request
     * cannot move another question's option.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function optionRules(): array
    {
        $rules = parent::optionRules();
        $rules['options.*.id'][] = Rule::exists(QuestionOption::class, 'id')
            ->where('question_id', $this->question()->id);

        return $rules;
    }

    protected function hasOptionRules(): bool
    {
        return ! $this->isLocked();
    }

    private function isLocked(): bool
    {
        // Cached: rules() and after() both ask, but it is one query.
        return $this->locked ??= $this->question()->isLocked();
    }

    private function question(): Question
    {
        return $this->route('question');
    }
}
