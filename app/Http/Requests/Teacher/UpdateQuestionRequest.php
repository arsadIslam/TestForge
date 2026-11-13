<?php

namespace App\Http\Requests\Teacher;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends StoreQuestionRequest
{
    private ?bool $locked = null;

    /**
     * Locked question (in a published test): options are not validated at all (the form
     * doesn't send them and QuestionService ignores them), so its type can't change either.
     * The question's own image can still change, like its text.
     *
     * An option id must belong to the question being edited, so a crafted request
     * cannot move another question's option.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->isLocked()) {
            return $rules;
        }

        $rules['options.*.id'][] = Rule::exists(QuestionOption::class, 'id')
            ->where('question_id', $this->question()->id);

        return $rules;
    }

    /**
     * Where to go after saving: the page that sent the teacher here (e.g. the test builder),
     * but ONLY if it is inside this app's teacher area — never an outside URL (open redirect).
     */
    public function returnTo(): ?string
    {
        $url = (string) $this->input('return_to');

        return str_starts_with($url, url('/teacher/')) ? $url : null;
    }

    protected function hasOptionRules(): bool
    {
        return ! $this->isLocked();
    }

    /**
     * @return array<int, bool>
     */
    protected function existingOptionImages(): array
    {
        return $this->question()->options()
            ->pluck('image_path', 'id')
            ->map(fn (?string $path): bool => $path !== null)
            ->all();
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
