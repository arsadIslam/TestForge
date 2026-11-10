<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Database\Eloquent\Builder;
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

    private function availableFor(Request $request): Builder
    {
        return Test::published()->available()->withAttemptInfoFor($request->user()->id);
    }
}
