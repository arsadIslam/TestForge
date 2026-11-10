<?php

namespace App\Console\Commands;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Services\AttemptService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Closes attempts whose time ran out while the student was away (tab closed, lost
 * connection, ...). Scheduled every minute in routes/console.php.
 */
class AutoSubmitExpiredAttempts extends Command
{
    protected $signature = 'attempts:auto-submit';

    protected $description = 'Auto-submit in-progress attempts whose time has expired';

    public function handle(AttemptService $attempts): int
    {
        $closed = 0;

        // Uses the (status, expires_at) index.
        // chunkById, not chunk: each processed row drops out of the WHERE clause, and
        // OFFSET-based chunk() would then skip rows; "WHERE id > last id" does not.
        Attempt::query()
            ->where('status', AttemptStatus::InProgress)
            ->where('expires_at', '<=', now())
            ->select('id')
            ->chunkById(100, function (Collection $chunk) use ($attempts, &$closed): void {
                foreach ($chunk as $attempt) {
                    if ($attempts->autoSubmitIfExpired($attempt)) {
                        $closed++;
                    }
                }
            });

        $this->info("Auto-submitted {$closed} attempt(s).");

        return self::SUCCESS;
    }
}
