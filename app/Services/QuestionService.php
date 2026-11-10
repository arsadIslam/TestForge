<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Saves a question together with its options and tags. Each public method runs in
 * one transaction, so a question can never be left half-saved (e.g. with no options).
 *
 * $data is the validated() array from Store/UpdateQuestionRequest:
 *   question columns + 'options' => [['id' => ?int, 'text' => string, 'is_correct' => bool], ...]
 *                    + 'tag_names' => list<string>
 */
class QuestionService
{
    public function create(User $teacher, array $data): Question
    {
        return DB::transaction(function () use ($teacher, $data): Question {
            // Through the relationship, so created_by is set without being fillable.
            $question = $teacher->questions()->create($this->questionAttributes($data));

            $this->syncOptions($question, $data['options']);
            $this->syncTags($question, $data['tag_names'] ?? []);

            return $question;
        });
    }

    public function update(Question $question, array $data): Question
    {
        return DB::transaction(function () use ($question, $data): Question {
            $attributes = $this->questionAttributes($data);

            // Frozen answer key: never change type/options of a question in a published/archived test,
            // even if a crafted request sends them (the form does not).
            if ($question->isLocked()) {
                unset($attributes['type']);
            } else {
                $this->syncOptions($question, $data['options']);
            }

            $question->update($attributes);
            $this->syncTags($question, $data['tag_names'] ?? []);

            return $question;
        });
    }

    public function delete(Question $question): void
    {
        // Test::questions() hides soft-deleted questions, so deleting one that a test uses
        // would silently shrink that test (and make its total_marks wrong).
        if ($question->tests()->exists()) {
            throw new BusinessRuleException('This question is used in a test. Remove it from the test before deleting it.');
        }

        $question->delete(); // soft delete
    }

    /**
     * @return array<string, mixed>
     */
    private function questionAttributes(array $data): array
    {
        return Arr::only($data, [
            'topic_id', 'type', 'body', 'explanation', 'difficulty', 'marks', 'negative_marks', 'is_active',
        ]);
    }

    /**
     * Sync by id: update options that are kept, insert new ones, delete removed ones.
     * Re-creating every option on each save would change option ids for no reason.
     *
     * @param  list<array{id?: int|string|null, text: string, is_correct: bool|string|int}>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        $existing = $question->options()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($options) as $index => $option) {
            $values = [
                'option_text' => $option['text'],
                'is_correct' => filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN),
                'sort_order' => $index + 1, // order on screen = order saved
            ];

            // Only ids that belong to THIS question are updated; anything else is treated as new.
            $current = $existing->get((int) ($option['id'] ?? 0));

            if ($current) {
                $current->update($values);
                $keptIds[] = $current->id;
            } else {
                $keptIds[] = $question->options()->create($values)->id;
            }
        }

        $question->options()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Teachers type tags as free text; existing tags are reused by slug, new ones created.
     *
     * @param  list<string>  $names
     */
    private function syncTags(Question $question, array $names): void
    {
        $tagIds = collect($names)
            ->map(fn (string $name): int => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id)
            ->all();

        $question->tags()->sync($tagIds);
    }
}
