<?php

namespace App\Http\Controllers\Web\Student;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestController extends Controller
{
    /**
     * Published tests inside their time window, with this student's attempt info.
     */
    public function index(Request $request): View
    {
        $tests = $this->availableFor($request)->latest('published_at')->paginate(10);

        return view('student.tests.index', ['tests' => $tests]);
    }

    /**
     * Instructions page with the Start / Resume button. {test:slug} in the route.
     */
    public function show(Request $request, string $slug): View
    {
        $test = $this->availableFor($request)->where('slug', $slug)->firstOrFail(); // 404 for drafts/closed tests

        return view('student.tests.show', ['test' => $test]);
    }

    /**
     * withCount with a constrained, aliased count → one sub-select, no N+1:
     *   (SELECT COUNT(*) FROM attempts WHERE test_id = tests.id AND user_id = ?) AS my_attempts_count
     */
    private function availableFor(Request $request): Builder
    {
        $studentId = $request->user()->id;

        return Test::published()
            ->available()
            ->withCount([
                'questions',
                'attempts as my_attempts_count' => fn (Builder $q) => $q->where('user_id', $studentId),
            ])
            ->with(['attempts' => fn (HasMany $q) => $q
                ->where('user_id', $studentId)
                ->where('status', AttemptStatus::InProgress),
            ]);
    }
}
