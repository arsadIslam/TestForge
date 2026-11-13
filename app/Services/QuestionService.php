<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tag;
use App\Models\User;
use App\Support\QuestionImages;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;

/**
 * Saves a question together with its options, tags and images. Each public method runs
 * in one transaction, so a question can never be left half-saved (e.g. with no options).
 *
 * $data is the validated() array from Store/UpdateQuestionRequest (or the CSV importer):
 *   question columns
 *   + 'image' => ?File, 'remove_image' => bool
 *   + 'options' => [['id' => ?int, 'text' => ?string, 'is_correct' => bool,
 *                    'image' => ?File, 'remove_image' => bool], ...]
 *   + 'tag_names' => list<string>
 *
 * Images: see App\Support\QuestionImages (random names, delete-after-commit, cleanup on failure).
 * Pass your own QuestionImages when several saves belong to one bigger transaction (the importer),
 * so all their files can be discarded together if it fails.
 */
class QuestionService
{
    public function create(User $teacher, array $data, ?QuestionImages $images = null): Question
    {
        $images ??= new QuestionImages;

        return $this->guard($images, fn (): Question => DB::transaction(function () use ($teacher, $data, $images): Question {
            // Through the relationship, so created_by is set without being fillable.
            $question = $teacher->questions()->create($this->questionAttributes($data));

            $this->syncQuestionImage($question, $data, $images);
            $this->syncOptions($question, $data['options'], $images);
            $this->syncTags($question, $data['tag_names'] ?? []);

            return $question;
        }));
    }

    public function update(Question $question, array $data, ?QuestionImages $images = null): Question
    {
        $images ??= new QuestionImages;

        return $this->guard($images, fn (): Question => DB::transaction(function () use ($question, $data, $images): Question {
            $attributes = $this->questionAttributes($data);

            // Frozen answer key: never change type/options of a question in a published/archived test,
            // even if a crafted request sends them (the form does not). Its own image may still change.
            if ($question->isLocked()) {
                unset($attributes['type']);
            } else {
                $this->syncOptions($question, $data['options'], $images);
            }

            $question->update($attributes);
            $this->syncQuestionImage($question, $data, $images);
            $this->syncTags($question, $data['tag_names'] ?? []);

            return $question;
        }));
    }

    public function delete(Question $question): void
    {
        // Test::questions() hides soft-deleted questions, so deleting one that a test uses
        // would silently shrink that test (and make its total_marks wrong).
        if ($question->tests()->exists()) {
            throw new BusinessRuleException('This question is used in a test. Remove it from the test before deleting it.');
        }

        // Soft delete: the images stay, because old results still show this question.
        $question->delete();
    }

    /**
     * Run a save; if it throws, delete any image files it had already stored.
     *
     * @template T
     *
     * @param  callable(): T  $save
     * @return T
     */
    private function guard(QuestionImages $images, callable $save): mixed
    {
        try {
            return $save();
        } catch (Throwable $e) {
            $images->discardStored();

            throw $e;
        }
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

    private function syncQuestionImage(Question $question, array $data, QuestionImages $images): void
    {
        $question->image_path = $this->resolveImage(
            $question->image_path,
            $data['image'] ?? null,
            (bool) ($data['remove_image'] ?? false),
            $images,
        );

        $question->save();
    }

    /**
     * New file → store it and drop the old one; "remove" → drop it; otherwise keep it.
     */
    private function resolveImage(?string $current, mixed $newFile, bool $remove, QuestionImages $images): ?string
    {
        if ($newFile instanceof File) {
            $images->deleteAfterCommit($current);

            return $images->store($newFile);
        }

        if ($remove) {
            $images->deleteAfterCommit($current);

            return null;
        }

        return $current;
    }

    /**
     * Sync by id: update options that are kept, insert new ones, delete removed ones.
     * Re-creating every option on each save would change option ids for no reason.
     *
     * @param  list<array<string, mixed>>  $options
     */
    private function syncOptions(Question $question, array $options, QuestionImages $images): void
    {
        $existing = $question->options()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($options) as $index => $option) {
            // Only ids that belong to THIS question are updated; anything else is treated as new.
            $current = $existing->get((int) ($option['id'] ?? 0)) ?? new QuestionOption;

            $text = trim((string) ($option['text'] ?? ''));

            $current->forceFill([
                'question_id' => $question->id,
                'option_text' => $text === '' ? null : $text, // image-only option
                'is_correct' => filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN),
                'sort_order' => $index + 1, // order on screen = order saved
                'image_path' => $this->resolveImage(
                    $current->image_path,
                    $option['image'] ?? null,
                    filter_var($option['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    $images,
                ),
            ])->save();

            $keptIds[] = $current->id;
        }

        $removed = $existing->except($keptIds);

        foreach ($removed as $option) {
            $images->deleteAfterCommit($option->image_path);
        }

        // modelKeys(), not keys(): Eloquent's except() re-indexes the collection from 0,
        // so keys() would be [0, 1, …] instead of option ids.
        $question->options()->whereKey($removed->modelKeys())->delete();
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
