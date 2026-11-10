<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Services\AnalyticsService;
use App\Services\LeaderboardService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AnalyticsController extends Controller implements HasMiddleware
{
    /**
     * TestPolicy@viewAnalytics: the teacher who owns the test (or an admin).
     */
    public static function middleware(): array
    {
        return [new Middleware('can:viewAnalytics,test')];
    }

    public function show(Test $test, AnalyticsService $analytics, LeaderboardService $leaderboard): View
    {
        return view('teacher.tests.analytics', self::data($test, $analytics, $leaderboard));
    }

    /**
     * Shared with the admin report page, which shows the same numbers in the admin layout.
     *
     * @return array<string, mixed>
     */
    public static function data(Test $test, AnalyticsService $analytics, LeaderboardService $leaderboard): array
    {
        return [
            'test' => $test,
            'summary' => $analytics->testSummary($test->id),
            'toughest' => $analytics->toughestQuestions($test->id),
            'leaders' => array_slice($leaderboard->top($test), 0, 10),
            'results' => $test->results()
                ->with(['student:id,name', 'attempt:id,attempt_no'])
                ->orderByDesc('score')
                ->orderBy('time_taken_seconds')
                ->paginate(15),
        ];
    }
}
