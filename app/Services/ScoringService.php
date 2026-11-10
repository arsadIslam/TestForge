<?php

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Events\ResultPublished;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Scores a submitted attempt and writes its Result.
 *
 * Rules:
 *  - single choice:   correct if the one selected option is the correct one;
 *  - multiple choice: correct only if the selected set EQUALS the set of correct options
 *                     (no partial marks: missing one, or adding a wrong one, is wrong);
 *  - correct → +marks, wrong → −negative_marks, unanswered → 0.
 * Marks come from the test_question pivot (copied when the question was attached),
 * so editing a question later never changes a result.
 */
class ScoringService
{
    /**
     * Idempotent: running it twice for the same attempt (a retried or duplicated job)
     * returns the existing result instead of scoring again. Guarded twice — by the
     * attempt row lock + status check, and by the unique key on results.attempt_id.
     */
    public function evaluate(Attempt $attempt): Result
    {
        return DB::transaction(function () use ($attempt): Result {
            $attempt = Attempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($attempt->status === AttemptStatus::Evaluated) {
                return Result::query()->where('attempt_id', $attempt->id)->firstOrFail();
            }

            if ($attempt->status === AttemptStatus::InProgress) {
                throw new LogicException("Attempt {$attempt->id} is still in progress and cannot be scored yet.");
            }

            $attempt->load('test');

            // Every question in the test, with ONLY its correct options loaded.
            $questions = $attempt->test->questions()
                ->with(['options' => fn ($q) => $q->where('is_correct', true)->select('id', 'question_id')])
                ->get()
                ->keyBy('id');

            $answers = $attempt->answers()->with('selectedOptions:id')->get();

            $score = 0.0;
            $correct = 0;
            $wrong = 0;
            $rows = [];

            foreach ($answers as $answer) {
                $question = $questions->get($answer->question_id);
                $selectedIds = $answer->selectedOptions->pluck('id')->all();

                if ($question === null || $selectedIds === []) {
                    continue; // nothing selected → counts as skipped (0 marks)
                }

                $outcome = $this->scoreAnswer(
                    $selectedIds,
                    $question->options->pluck('id')->all(),
                    (float) $question->pivot->marks,
                    (float) $question->pivot->negative_marks,
                );

                $outcome['is_correct'] ? $correct++ : $wrong++;
                $score += $outcome['marks'];

                $rows[] = [
                    'id' => $answer->id,
                    'attempt_id' => $answer->attempt_id,
                    'question_id' => $answer->question_id,
                    'answered_at' => $answer->answered_at,
                    'is_correct' => $outcome['is_correct'],
                    'marks_awarded' => $outcome['marks'],
                ];
            }

            // One query for all answers: INSERT ... ON DUPLICATE KEY UPDATE on the primary key.
            // (Every row already exists, so this only ever updates is_correct/marks_awarded.)
            if ($rows !== []) {
                AttemptAnswer::query()->upsert($rows, uniqueBy: ['id'], update: ['is_correct', 'marks_awarded']);
            }

            $answered = $correct + $wrong;
            $durationSeconds = $attempt->test->duration_minutes * 60;

            $result = Result::create([
                'attempt_id' => $attempt->id,
                'test_id' => $attempt->test_id, // denormalised for a fast leaderboard
                'user_id' => $attempt->user_id,
                'score' => round($score, 2),
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'skipped_count' => $questions->count() - $answered,
                'accuracy' => $answered > 0 ? round($correct / $answered * 100, 2) : 0,
                // Clamped: never negative, never more than the test allows.
                'time_taken_seconds' => (int) min(max(0, $attempt->started_at->diffInSeconds($attempt->submitted_at)), $durationSeconds),
                'is_passed' => round($score, 2) >= (float) $attempt->test->pass_marks,
                'evaluated_at' => now(),
            ]);

            $attempt->forceFill(['status' => AttemptStatus::Evaluated])->save();

            // ShouldDispatchAfterCommit → listeners run only after this transaction commits.
            ResultPublished::dispatch($result);

            return $result;
        });
    }

    /**
     * The scoring rule on its own — no database — so it is easy to unit test.
     *
     * Comparing sets covers both question types: for single choice the correct set has
     * exactly one option, so "sets are equal" means "picked the correct option".
     *
     * @param  list<int>  $selectedIds
     * @param  list<int>  $correctIds
     * @return array{is_correct: bool, marks: float}
     */
    public function scoreAnswer(array $selectedIds, array $correctIds, float $marks, float $negativeMarks): array
    {
        $selected = array_values(array_unique(array_map('intval', $selectedIds)));
        $correct = array_values(array_unique(array_map('intval', $correctIds)));
        sort($selected);
        sort($correct);

        $isCorrect = $selected !== [] && $selected === $correct;

        return [
            'is_correct' => $isCorrect,
            'marks' => $isCorrect ? $marks : -$negativeMarks,
        ];
    }
}
