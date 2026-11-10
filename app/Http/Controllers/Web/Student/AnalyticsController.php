<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        return view('student.analytics', [
            'subjects' => $analytics->subjectAccuracy($request->user()->id), // weakest first
            'summary' => $analytics->studentSummary($request->user()->id),
        ]);
    }
}
