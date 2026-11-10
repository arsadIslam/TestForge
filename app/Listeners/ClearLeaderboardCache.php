<?php

namespace App\Listeners;

use App\Events\ResultPublished;
use App\Services\LeaderboardService;

/**
 * A new result can change the ranking, so drop that test's cached leaderboard;
 * the next visitor rebuilds it. Auto-discovered by Laravel from the type-hinted event.
 */
class ClearLeaderboardCache
{
    public function __construct(private LeaderboardService $leaderboard) {}

    public function handle(ResultPublished $event): void
    {
        $this->leaderboard->forget($event->result->test_id);
    }
}
