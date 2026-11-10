<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // One GROUP BY query each instead of one COUNT per role/status.
        // toBase() skips model hydration (and enum casting) — we only need raw numbers.
        $usersByRole = User::query()->toBase()
            ->selectRaw('role, COUNT(*) AS total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $testsByStatus = Test::query()->toBase() // toBase() still applies the soft-delete scope
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'usersByRole' => $usersByRole,
            'testsByStatus' => $testsByStatus,
            'counts' => [
                'subjects' => Subject::count(),
                'topics' => Topic::count(),
                'questions' => Question::count(),
                'attempts' => Attempt::count(),
            ],
        ]);
    }
}
