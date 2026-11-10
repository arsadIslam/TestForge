<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Per-test leaderboard, cached for 5 minutes. (The ranking query itself is added in Step 10.)
 *
 * The cache key lives here, in one place, so the code that writes the cache and the
 * listener that clears it can never disagree about it. The database cache driver has
 * no tags, so each test simply gets its own key.
 */
class LeaderboardService
{
    public const CACHE_TTL_SECONDS = 300;

    public function cacheKey(int $testId): string
    {
        return "leaderboard:test:{$testId}";
    }

    public function forget(int $testId): void
    {
        Cache::forget($this->cacheKey($testId));
    }
}
