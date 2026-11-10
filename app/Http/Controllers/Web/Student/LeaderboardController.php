<?php

namespace App\Http\Controllers\Web\Student;

use App\Enums\TestStatus;
use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    /**
     * GET /student/tests/{test:slug}/leaderboard — published or archived tests only.
     */
    public function show(Request $request, Test $test, LeaderboardService $leaderboard): View
    {
        abort_if($test->status === TestStatus::Draft, 404);

        return view('student.leaderboard', [
            'test' => $test,
            'entries' => $leaderboard->top($test),                      // cached 5 min
            'myRank' => $leaderboard->rankOf($test, $request->user()->id), // even outside the top 50
        ]);
    }
}
