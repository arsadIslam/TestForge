<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        $teacher = $request->user();
        $myTestIds = $teacher->tests()->select('id'); // sub-query, not a list loaded into PHP

        return view('teacher.dashboard', [
            'questionCount' => $teacher->questions()->count(),
            'testsByStatus' => $teacher->tests()->toBase()
                ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
            'resultCount' => Result::whereIn('test_id', $myTestIds)->count(),
            'untouched' => $analytics->publishedTestsWithoutAttempts($teacher->id), // key query #4
            'recentResults' => Result::whereIn('test_id', $myTestIds)
                ->with(['student:id,name', 'test:id,title'])
                ->latest('evaluated_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
