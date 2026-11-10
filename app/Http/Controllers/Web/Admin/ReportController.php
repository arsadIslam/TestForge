<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Teacher\AnalyticsController;
use App\Models\Test;
use App\Services\AnalyticsService;
use App\Services\LeaderboardService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * "Admin sees all reports": every teacher's tests, and the same analytics a teacher sees.
 */
class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:viewAnalytics,test', only: ['show'])];
    }

    public function index(AnalyticsService $analytics): View
    {
        $tests = Test::query()
            ->with('creator:id,name')
            ->withCount(['questions', 'results'])
            ->withAvg('results', 'score') // → results_avg_score
            ->latest('id')
            ->paginate(15);

        return view('admin.reports.index', [
            'tests' => $tests,
            'untouched' => $analytics->publishedTestsWithoutAttempts(), // key query #4, all teachers
        ]);
    }

    public function show(Test $test, AnalyticsService $analytics, LeaderboardService $leaderboard): View
    {
        return view('admin.reports.show', AnalyticsController::data($test, $analytics, $leaderboard));
    }
}
