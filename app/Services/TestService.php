<?php

namespace App\Services;

use App\Enums\TestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The test builder's business rules.
 *
 * Every method that changes a test's questions or status:
 *  1. runs in a transaction,
 *  2. re-reads the test row with SELECT ... FOR UPDATE (lockForUpdate),
 *  3. re-checks the status under that lock.
 * So "attach in one tab" and "publish in another" are serialised: the second waits for
 * the first to commit, then sees the new status. The policy check that ran before the
 * controller is not enough on its own, because the status can change between the two.
 */
class TestService
{
    public function create(User $teacher, array $data): Test
    {
        return $teacher->tests()->create([...$data, 'slug' => $this->uniqueSlug($data['title'])]);
    }

    /**
     * Details only (title, schedule, ...). The slug stays the same so links never break.
     */
    public function update(Test $test, array $data): Test
    {
        $test->update($data);

        return $test;
    }

    /**
     * Attach questions in the given order, after the ones already in the test.
     * Marks and negative marks are COPIED into the pivot, so editing the question
     * later never changes this test or its results.
     *
     * @param  list<int>  $questionIds
     * @return int number of questions actually added (already-attached / inactive ids are skipped)
     */
    public function attachQuestions(Test $test, array $questionIds): int
    {
        return DB::transaction(function () use ($test, $questionIds): int {
            $test = $this->lockWithStatus($test, TestStatus::Draft, 'Questions can only be added to a draft test.');

            $added = $this->appendQuestions($test, $questionIds);
            $this->refreshTotalMarks($test);

            return $added;
        });
    }

    /**
     * Randomly pick questions by rules, e.g. "Algebra · hard · 4" + "Geometry · any · 6".
     * The teacher then edits the result in the builder (swap, remove, reorder, add).
     *
     * Each rule draws ACTIVE questions that are not already in the test and not drawn by an
     * earlier rule. All-or-nothing: if any rule can't be filled, the transaction rolls back
     * (including a "replace" that already removed the old questions) and the error says
     * exactly what is available.
     *
     * @param  list<array{subject_id: int, topic_id?: ?int, difficulty?: ?string, count: int}>  $rules
     * @return int number of questions added
     */
    public function generate(Test $test, array $rules, bool $replace = false): int
    {
        return DB::transaction(function () use ($test, $rules, $replace): int {
            $test = $this->lockWithStatus($test, TestStatus::Draft, 'Questions can only be generated for a draft test.');

            if ($replace) {
                DB::table('test_question')->where('test_id', $test->id)->delete();
            }

            $exclude = DB::table('test_question')->where('test_id', $test->id)->pluck('question_id')->all();
            $picked = [];
            $pickedByRule = [];
            $shortfalls = [];

            // Most specific rules draw first (topic + difficulty, topic, difficulty, whole subject),
            // so a broad rule like "any Maths topic" can't use up the questions that a narrow rule
            // like "Algebra · hard" needs. The picks are still added in the teacher's rule order.
            $bySpecificity = collect($rules)->sortByDesc(
                fn (array $rule): int => (($rule['topic_id'] ?? null) ? 2 : 0) + (($rule['difficulty'] ?? null) ? 1 : 0)
            );

            foreach ($bySpecificity as $index => $rule) {
                $count = (int) $rule['count'];

                $ids = Question::active()
                    ->whereIn('topic_id', Topic::where('subject_id', $rule['subject_id'])->select('id'))
                    ->when($rule['topic_id'] ?? null, fn ($q, $topicId) => $q->where('topic_id', $topicId))
                    ->when($rule['difficulty'] ?? null, fn ($q, $difficulty) => $q->where('difficulty', $difficulty))
                    ->whereNotIn('id', [...$exclude, ...$picked])
                    ->inRandomOrder()
                    ->limit($count)
                    ->pluck('id')
                    ->all();

                if (count($ids) < $count) {
                    $shortfalls[] = $this->ruleLabel($rule).': asked '.$count.', only '.count($ids).' available';
                }

                array_push($picked, ...$ids);
                $pickedByRule[$index] = $ids;
            }

            if ($shortfalls !== []) {
                throw new BusinessRuleException('Not enough matching questions — '.implode('; ', $shortfalls).'. Nothing was changed.');
            }

            ksort($pickedByRule); // back to the teacher's rule order
            $added = $this->appendQuestions($test, array_merge(...array_values($pickedByRule)));
            $this->refreshTotalMarks($test);

            return $added;
        });
    }

    /**
     * Replace one question with a random active question of the SAME topic and difficulty
     * that isn't in the test yet, keeping its position. Marks are re-copied from the new question.
     */
    public function swapQuestion(Test $test, Question $question): Question
    {
        return DB::transaction(function () use ($test, $question): Question {
            $test = $this->lockWithStatus($test, TestStatus::Draft, 'Questions can only be swapped in a draft test.');

            $inTest = DB::table('test_question')->where('test_id', $test->id)->pluck('question_id')->all();

            $replacement = Question::active()
                ->where('topic_id', $question->topic_id)
                ->where('difficulty', $question->difficulty)
                ->whereNotIn('id', $inTest)
                ->inRandomOrder()
                ->first()
                ?? throw new BusinessRuleException(
                    "There is no other active {$question->difficulty->value} question in this topic to swap in."
                );

            // Same row, same sort_order: only the question (and its copied marks) change.
            DB::table('test_question')
                ->where('test_id', $test->id)
                ->where('question_id', $question->id)
                ->update([
                    'question_id' => $replacement->id,
                    'marks' => $replacement->marks,
                    'negative_marks' => $replacement->negative_marks,
                ]);

            $this->refreshTotalMarks($test);

            return $replacement;
        });
    }

    /**
     * Append questions after the ones already in the test, in the given order. Marks and
     * negative marks are COPIED into the pivot, so editing a question later never changes
     * this test or its results. Already-attached / inactive ids are skipped.
     * The caller holds the test's row lock.
     *
     * @param  list<int|string>  $questionIds
     */
    private function appendQuestions(Test $test, array $questionIds): int
    {
        $pivot = DB::table('test_question')->where('test_id', $test->id);
        $alreadyAttached = (clone $pivot)->pluck('question_id')->all();
        $nextSortOrder = (int) (clone $pivot)->max('sort_order');

        $questions = Question::active()
            ->whereIn('id', $questionIds)
            ->whereNotIn('id', $alreadyAttached)
            ->get(['id', 'marks', 'negative_marks'])
            ->keyBy('id');

        $rows = [];
        foreach ($questionIds as $id) { // keep the given order
            if ($question = $questions->pull($id)) {
                $rows[$question->id] = [
                    'sort_order' => ++$nextSortOrder,
                    'marks' => $question->marks,
                    'negative_marks' => $question->negative_marks,
                ];
            }
        }

        $test->questions()->attach($rows);

        return count($rows);
    }

    /**
     * "Mathematics › Algebra · hard" for error messages.
     *
     * @param  array{subject_id: int, topic_id?: ?int, difficulty?: ?string}  $rule
     */
    private function ruleLabel(array $rule): string
    {
        $label = Subject::whereKey($rule['subject_id'])->value('name');

        if ($rule['topic_id'] ?? null) {
            $label .= ' › '.Topic::whereKey($rule['topic_id'])->value('name');
        }

        return $label.(($rule['difficulty'] ?? null) ? ' · '.$rule['difficulty'] : '');
    }

    public function detachQuestion(Test $test, Question $question): void
    {
        DB::transaction(function () use ($test, $question): void {
            $test = $this->lockWithStatus($test, TestStatus::Draft, 'Questions can only be removed from a draft test.');

            $test->questions()->detach($question->id);

            // Close the gap so sort_order stays 1..n.
            $remaining = DB::table('test_question')->where('test_id', $test->id)->orderBy('sort_order')->pluck('question_id')->all();
            $this->writeOrder($test, $remaining);
            $this->refreshTotalMarks($test);
        });
    }

    /**
     * @param  list<int>  $orderedQuestionIds  every attached question id, in the new order
     */
    public function reorderQuestions(Test $test, array $orderedQuestionIds): void
    {
        DB::transaction(function () use ($test, $orderedQuestionIds): void {
            $test = $this->lockWithStatus($test, TestStatus::Draft, 'Only a draft test can be reordered.');

            // Checked under the lock: the list must be exactly the questions in the test right now.
            $current = DB::table('test_question')->where('test_id', $test->id)->pluck('question_id')->sort()->values()->all();
            $given = collect($orderedQuestionIds)->map(fn ($id): int => (int) $id)->sort()->values()->all();

            if ($current !== $given) {
                throw new BusinessRuleException('The questions in this test changed while you were reordering. Please reload and try again.');
            }

            $this->writeOrder($test, $orderedQuestionIds);
        });
    }

    public function publish(Test $test): Test
    {
        return DB::transaction(function () use ($test): Test {
            $test = $this->lockWithStatus($test, TestStatus::Draft, 'Only a draft test can be published.');

            // One query for all the numbers the checks need.
            $stats = DB::table('test_question')
                ->join('questions', 'questions.id', '=', 'test_question.question_id')
                ->where('test_question.test_id', $test->id)
                ->selectRaw('COUNT(*) AS question_count')
                ->selectRaw('SUM(questions.is_active = 0) AS inactive_count')
                ->selectRaw('COALESCE(SUM(test_question.marks), 0) AS total_marks')
                ->first();

            $totalMarks = (float) $stats->total_marks;

            if ((int) $stats->question_count === 0) {
                throw new BusinessRuleException('Add at least one question before publishing.');
            }

            if ((int) $stats->inactive_count > 0) {
                throw new BusinessRuleException("{$stats->inactive_count} question(s) in this test are inactive. Remove or reactivate them before publishing.");
            }

            if ((float) $test->pass_marks > $totalMarks) {
                throw new BusinessRuleException("Pass marks ({$test->pass_marks}) cannot be higher than the total marks ({$totalMarks}).");
            }

            if ($test->ends_at?->isPast()) {
                throw new BusinessRuleException('The end date has already passed. Update the schedule before publishing.');
            }

            $test->forceFill([
                'status' => TestStatus::Published,
                'published_at' => now(),
                'total_marks' => $totalMarks,
            ])->save();

            return $test;
        });
    }

    /**
     * Archived tests can no longer be started; existing attempts and results stay.
     */
    public function archive(Test $test): Test
    {
        return DB::transaction(function () use ($test): Test {
            $test = $this->lockWithStatus($test, TestStatus::Published, 'Only a published test can be archived.');
            $test->forceFill(['status' => TestStatus::Archived])->save();

            return $test;
        });
    }

    /**
     * SELECT ... FOR UPDATE on the test row, then check its status. Must run inside a transaction.
     */
    private function lockWithStatus(Test $test, TestStatus $expected, string $message): Test
    {
        $locked = Test::query()->whereKey($test->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status !== $expected) {
            throw new BusinessRuleException($message);
        }

        return $locked;
    }

    /**
     * sort_order = position in the list (1-based). One UPDATE per row; fine for a test's
     * size. (At scale this becomes a single UPDATE ... SET sort_order = CASE question_id ... END.)
     *
     * @param  list<int|string>  $orderedQuestionIds
     */
    private function writeOrder(Test $test, array $orderedQuestionIds): void
    {
        foreach (array_values($orderedQuestionIds) as $index => $questionId) {
            DB::table('test_question')
                ->where('test_id', $test->id)
                ->where('question_id', $questionId)
                ->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * total_marks is always derived from the pivot rows — never typed in, never incremented.
     */
    private function refreshTotalMarks(Test $test): void
    {
        $test->forceFill([
            'total_marks' => DB::table('test_question')->where('test_id', $test->id)->sum('marks'),
        ])->save();
    }

    /**
     * "Physics Mock" → physics-mock, physics-mock-2, ... withTrashed(): a soft-deleted
     * test still holds its slug in the unique index.
     */
    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 200, '') ?: 'test';
        $slug = $base;

        for ($i = 2; Test::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
