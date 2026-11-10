<?php

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Exceptions\BusinessRuleException;
use App\Jobs\EvaluateAttempt;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * A student's attempt: start → save answers → submit.
 *
 * Every write runs in a transaction and takes a row lock first (SELECT ... FOR UPDATE),
 * then re-checks the rules under that lock. The browser's timer is only a convenience:
 * the server decides whether time is up, using expires_at.
 */
class AttemptService
{
    /**
     * Start a new attempt, or return the one already in progress (a second "Start" click,
     * or reopening the test in another tab, resumes instead of failing).
     */
    public function start(User $student, Test $test, ?string $ip = null): Attempt
    {
        try {
            return DB::transaction(function () use ($student, $test, $ip): Attempt {
                // Lock the STUDENT's row: two start requests from the same student run one after
                // the other, while different students never wait for each other (locking the
                // test row would make every student starting this test queue behind each other).
                User::query()->whereKey($student->id)->lockForUpdate()->first();

                $inProgress = Attempt::query()
                    ->where('test_id', $test->id)
                    ->where('user_id', $student->id)
                    ->where('status', AttemptStatus::InProgress)
                    ->first();

                if ($inProgress && ! $inProgress->expires_at->isPast()) {
                    return $inProgress;
                }

                if ($inProgress) {
                    // Time ran out but the scheduled auto-submit hasn't run yet: close it now.
                    $this->finish($inProgress, auto: true);
                }

                // Fresh read: the test may have been archived or closed since the page loaded.
                $test = Test::published()->available()->whereKey($test->id)->first()
                    ?? throw new BusinessRuleException('This test is not open for attempts right now.');

                $used = Attempt::query()->where('test_id', $test->id)->where('user_id', $student->id)->count();

                if ($used >= $test->max_attempts) {
                    throw new BusinessRuleException("You have used all {$test->max_attempts} attempt(s) for this test.");
                }

                $startedAt = now();

                return Attempt::create([
                    'test_id' => $test->id,
                    'user_id' => $student->id,
                    'attempt_no' => $used + 1,
                    'status' => AttemptStatus::InProgress,
                    'started_at' => $startedAt,
                    'expires_at' => $startedAt->copy()->addMinutes($test->duration_minutes),
                    'ip_address' => $ip,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Last line of defence: unique(test_id, user_id, attempt_no) rejected a duplicate.
            throw new BusinessRuleException('This attempt has already been started. Please reload the page.');
        }
    }

    /**
     * Save (or change, or clear) the answer to one question. Called by the auto-save fetch.
     *
     * @param  list<int>  $optionIds  empty = clear the answer (counts as skipped)
     */
    public function saveAnswer(Attempt $attempt, Question $question, array $optionIds): void
    {
        DB::transaction(function () use ($attempt, $question, $optionIds): void {
            $attempt = $this->lockInProgress($attempt);

            // Server-side clock, whatever the browser's timer says.
            if ($attempt->expires_at->isPast()) {
                throw new BusinessRuleException('Time is up. Answers can no longer be changed.');
            }

            $inTest = DB::table('test_question')
                ->where('test_id', $attempt->test_id)
                ->where('question_id', $question->id)
                ->exists();

            if (! $inTest) {
                throw new BusinessRuleException('This question is not part of your test.');
            }

            if ($question->type === QuestionType::Single && count($optionIds) > 1) {
                throw new BusinessRuleException('Only one option can be selected for this question.');
            }

            if ($optionIds === []) {
                // Clearing an answer deletes the row (its option rows cascade), so it counts as skipped.
                AttemptAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $question->id)->delete();

                return;
            }

            // INSERT ... ON DUPLICATE KEY UPDATE on unique(attempt_id, question_id):
            // one row per question per attempt, however many times it is saved.
            AttemptAnswer::query()->upsert(
                [['attempt_id' => $attempt->id, 'question_id' => $question->id, 'answered_at' => now()]],
                uniqueBy: ['attempt_id', 'question_id'],
                update: ['answered_at'],
            );

            $answer = AttemptAnswer::query()
                ->where('attempt_id', $attempt->id)
                ->where('question_id', $question->id)
                ->firstOrFail();

            // attempt_answer_options: one row per selected option (handles multi-select).
            $answer->selectedOptions()->sync($optionIds);
        });
    }

    /**
     * Student clicks "Submit" (or the timer reaches zero in the browser).
     * The lock makes in_progress → submitted happen exactly once, so a double click
     * can never submit — or score — the attempt twice.
     */
    public function submit(Attempt $attempt): Attempt
    {
        return DB::transaction(function () use ($attempt): Attempt {
            $attempt = $this->lockInProgress($attempt);

            // Submitted after the deadline (e.g. the tab was closed and reopened) → treated as auto-submitted.
            return $this->finish($attempt, auto: $attempt->expires_at->isPast());
        });
    }

    /**
     * The test's questions (with options) in the order THIS attempt shows them.
     * With shuffle on, the order is random per attempt but stable across page loads:
     * the shuffle is seeded with the attempt id, so no extra column is needed.
     *
     * @return Collection<int, Question>
     */
    public function questionsFor(Attempt $attempt): Collection
    {
        $questions = $attempt->test->questions()->with('options')->get();

        if (! $attempt->test->shuffle_questions) {
            return $questions;
        }

        $randomizer = new Randomizer(new Mt19937($attempt->id));

        return new Collection($randomizer->shuffleArray($questions->all()));
    }

    /**
     * Must be called inside a transaction (the caller already holds the attempt's lock).
     */
    /**
     * Called by the scheduled attempts:auto-submit command for each expired attempt.
     * Re-checked under the lock: if the student submitted a moment earlier, nothing happens.
     *
     * @return bool true if this call closed the attempt
     */
    public function autoSubmitIfExpired(Attempt $attempt): bool
    {
        return DB::transaction(function () use ($attempt): bool {
            $locked = Attempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== AttemptStatus::InProgress || ! $locked->expires_at->isPast()) {
                return false;
            }

            $this->finish($locked, auto: true);

            return true;
        });
    }

    /**
     * in_progress → submitted / auto_submitted, then queue the scoring job.
     * Must be called inside a transaction, by a caller that holds the attempt's lock —
     * so this transition, and therefore the job dispatch, happens exactly once.
     */
    private function finish(Attempt $attempt, bool $auto): Attempt
    {
        $attempt->forceFill([
            'status' => $auto ? AttemptStatus::AutoSubmitted : AttemptStatus::Submitted,
            // An auto-submitted attempt ends at its deadline, not when we noticed it.
            'submitted_at' => $auto ? $attempt->expires_at : now(),
        ])->save();

        // EvaluateAttempt calls afterCommit(): it is only pushed once this transaction commits.
        EvaluateAttempt::dispatch($attempt);

        return $attempt;
    }

    private function lockInProgress(Attempt $attempt): Attempt
    {
        $locked = Attempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status !== AttemptStatus::InProgress) {
            throw new BusinessRuleException('This attempt has already been submitted.');
        }

        return $locked;
    }
}
