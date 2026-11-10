<?php

namespace App\Services;

use App\Models\Test;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Per-test leaderboard built with MySQL 8 / MariaDB 10.2+ window functions.
 *
 * Each student is ranked by their BEST attempt (highest score, then fastest), so
 * a student with two attempts appears once. The top 50 is cached for 5 minutes;
 * the ResultPublished listener clears it whenever a new result arrives.
 */
class LeaderboardService
{
    public const CACHE_TTL_SECONDS = 300;

    public const TOP = 50;

    /**
     * Key query #1 — leaderboard with rank and percentile.
     *
     * Why two CTEs: a window function's result can't be used in the WHERE of the
     * same SELECT, so "keep only rn = 1" needs a second level.
     *
     *   best   — ROW_NUMBER() numbers each student's attempts from best to worst;
     *            rn = 1 is their best one.
     *   ranked — RANK() gives equal scores (and equal times) the same rank and then
     *            skips (1, 2, 2, 4). PERCENT_RANK() = (rank − 1) / (n − 1) in ASCENDING
     *            order, i.e. the share of students this one beat: top student = 100.
     *            COUNT(*) OVER () = how many students took part, before the LIMIT.
     *
     * Eloquent / Query Builder equivalent (no CTE support in core, so fromSub()):
     *
     *   $best = DB::table('results')
     *       ->select('user_id', 'score', 'time_taken_seconds')
     *       ->selectRaw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY score DESC, time_taken_seconds, id) AS rn')
     *       ->where('test_id', $testId);
     *
     *   $ranked = DB::query()->fromSub($best, 'b')->where('b.rn', 1)
     *       ->select('b.user_id', 'b.score', 'b.time_taken_seconds')
     *       ->selectRaw('RANK() OVER (ORDER BY b.score DESC, b.time_taken_seconds) AS rank_no')
     *       ->selectRaw('PERCENT_RANK() OVER (ORDER BY b.score, b.time_taken_seconds DESC) AS percent_rank')
     *       ->selectRaw('COUNT(*) OVER () AS total_students');
     *
     *   DB::query()->fromSub($ranked, 'ranked')
     *       ->join('users as u', 'u.id', '=', 'ranked.user_id')
     *       ->select('ranked.*', 'u.name')
     *       ->orderBy('rank_no')->orderBy('u.name')->limit(50)->get();
     */
    private const RANKING_SQL = <<<'SQL'
        WITH best AS (
            SELECT r.user_id, r.score, r.time_taken_seconds,
                   ROW_NUMBER() OVER (
                       PARTITION BY r.user_id
                       ORDER BY r.score DESC, r.time_taken_seconds ASC, r.id ASC
                   ) AS rn
            FROM results r
            WHERE r.test_id = ?
        ),
        ranked AS (
            SELECT b.user_id, b.score, b.time_taken_seconds,
                   RANK()         OVER (ORDER BY b.score DESC, b.time_taken_seconds ASC) AS rank_no,
                   PERCENT_RANK() OVER (ORDER BY b.score ASC,  b.time_taken_seconds DESC) AS percent_rank,
                   COUNT(*)       OVER () AS total_students
            FROM best b
            WHERE b.rn = 1
        )
        SELECT ranked.rank_no, ranked.user_id, u.name, ranked.score, ranked.time_taken_seconds,
               ROUND(ranked.percent_rank * 100, 2) AS percentile, ranked.total_students
        FROM ranked
        JOIN users u ON u.id = ranked.user_id
        %s
        ORDER BY ranked.rank_no, u.name
        %s
        SQL;

    /**
     * Top 50 for a test, cached for 5 minutes.
     *
     * @return list<array{rank_no: int, user_id: int, name: string, score: string, time_taken_seconds: int, percentile: string, total_students: int}>
     */
    public function top(Test $test): array
    {
        return Cache::remember(
            $this->cacheKey($test->id),
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->run(sprintf(self::RANKING_SQL, '', 'LIMIT ?'), [$test->id, self::TOP]),
        );
    }

    /**
     * One student's position, even if they are outside the top 50. Not cached: the
     * window functions still run over ALL students; only the outer WHERE filters.
     *
     * @return array{rank_no: int, user_id: int, name: string, score: string, time_taken_seconds: int, percentile: string, total_students: int}|null
     */
    public function rankOf(Test $test, int $userId): ?array
    {
        return $this->run(sprintf(self::RANKING_SQL, 'WHERE ranked.user_id = ?', ''), [$test->id, $userId])[0] ?? null;
    }

    public function cacheKey(int $testId): string
    {
        // The database cache driver has no tags, so each test simply gets its own key.
        return "leaderboard:test:{$testId}";
    }

    public function forget(int $testId): void
    {
        Cache::forget($this->cacheKey($testId));
    }

    /**
     * @param  list<int>  $bindings
     * @return list<array<string, mixed>>
     */
    private function run(string $sql, array $bindings): array
    {
        return array_map(function (object $row): array {
            $row = (array) $row;
            $row['rank_no'] = (int) $row['rank_no'];
            $row['user_id'] = (int) $row['user_id'];
            $row['time_taken_seconds'] = (int) $row['time_taken_seconds'];
            $row['total_students'] = (int) $row['total_students'];

            return $row;
        }, DB::select($sql, $bindings));
    }
}
