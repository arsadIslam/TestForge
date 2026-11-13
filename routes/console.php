<?php

use Illuminate\Support\Facades\Schedule;

/*
| Everything here runs from ONE cron entry:
|   * * * * * cd /path/to/testforge && php artisan schedule:run >> /dev/null 2>&1
|
| withoutOverlapping(): if a run is still going when the next minute starts, the next
| one is skipped instead of starting a second copy (a cache lock; the database cache
| supports locks).
*/

// Close attempts whose time ran out (and queue their scoring).
Schedule::command('attempts:auto-submit')->everyMinute()->withoutOverlapping();

// Delete question-import uploads that were previewed but never confirmed or cancelled.
Schedule::command('imports:prune')->daily();

// Shared hosting: no long-running worker, so drain the queue once a minute and exit.
// --max-time keeps each run under a minute so runs never pile up.
if (config('queue.run_via_scheduler')) {
    Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
        ->everyMinute()
        ->withoutOverlapping();
}
