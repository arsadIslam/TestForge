<?php

use Illuminate\Support\Facades\Schedule;

/*
| Runs via one cron entry: * * * * * php /path/to/artisan schedule:run
| withoutOverlapping(): if a run takes longer than a minute, the next one is skipped
| instead of starting a second copy (uses a cache lock — the database cache supports it).
*/
Schedule::command('attempts:auto-submit')->everyMinute()->withoutOverlapping();
