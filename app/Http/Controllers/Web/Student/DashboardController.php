<?php

namespace App\Http\Controllers\Web\Student;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Services\AccessService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics, AccessService $access): View
    {
        $student = $request->user();

        return view('student.dashboard', [
            'summary' => $analytics->studentSummary($student->id),
            'access' => $access->accessFor($student),
            'inProgress' => $student->attempts()
                ->where('status', AttemptStatus::InProgress)
                ->where('expires_at', '>', now())
                ->with('test:id,title')
                ->get(),
            'beingMarked' => $student->attempts()
                ->whereIn('status', [AttemptStatus::Submitted, AttemptStatus::AutoSubmitted])
                ->with('test:id,title')
                ->get(),
            'results' => $student->results()
                ->with(['test:id,title,slug,total_marks', 'attempt:id,attempt_no'])
                ->latest('evaluated_at')
                ->paginate(10),
        ]);
    }
}
