<?php

namespace App\Jobs;

use App\Models\Attempt;
use App\Services\ScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scores one submitted attempt in the background (queue: database driver by default).
 *
 * Safe to run more than once — ScoringService::evaluate() is idempotent — so automatic
 * retries after a crash or a deadlock can never produce a second result.
 */
class EvaluateAttempt implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Seconds to wait before retry 2 and retry 3. */
    public array $backoff = [10, 60];

    public function __construct(public Attempt $attempt)
    {
        // Don't push the job until the submit transaction has committed; otherwise a fast
        // worker could pick it up and still see the attempt as "in_progress".
        $this->afterCommit();
    }

    public function handle(ScoringService $scoring): void
    {
        $scoring->evaluate($this->attempt);
    }

    /**
     * All retries used up. The job stays in failed_jobs and can be re-run with
     * `php artisan queue:retry all` once the problem is fixed.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('EvaluateAttempt failed', [
            'attempt_id' => $this->attempt->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
