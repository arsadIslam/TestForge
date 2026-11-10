<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\TestRequest;
use App\Models\Test;
use App\Services\TestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TestController extends Controller implements HasMiddleware
{
    /**
     * Every action is authorized against TestPolicy ('test' = the {test} route parameter).
     * update/delete/publish only pass for the owner while the test is a draft.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Test::class, only: ['index']),
            new Middleware('can:create,'.Test::class, only: ['create', 'store']),
            new Middleware('can:view,test', only: ['show']),
            new Middleware('can:update,test', only: ['edit', 'update']),
            new Middleware('can:delete,test', only: ['destroy']),
            new Middleware('can:publish,test', only: ['publish']),
            new Middleware('can:archive,test', only: ['archive']),
        ];
    }

    public function index(Request $request): View
    {
        $tests = $request->user()->tests()
            ->withCount(['questions', 'attempts'])
            ->latest('id')
            ->paginate(10);

        return view('teacher.tests.index', ['tests' => $tests]);
    }

    public function create(): View
    {
        return view('teacher.tests.create', [
            'test' => new Test(['duration_minutes' => 30, 'pass_marks' => 0, 'max_attempts' => 1]),
        ]);
    }

    public function store(TestRequest $request, TestService $tests): RedirectResponse
    {
        $test = $tests->create($request->user(), $request->validated());

        return redirect()->route('teacher.tests.questions.index', $test)
            ->with('success', 'Test created. Now add some questions.');
    }

    public function show(Test $test): View
    {
        $test->load(['questions.topic.subject'])->loadCount('attempts');

        return view('teacher.tests.show', ['test' => $test]);
    }

    public function edit(Test $test): View
    {
        return view('teacher.tests.edit', ['test' => $test]);
    }

    public function update(TestRequest $request, Test $test, TestService $tests): RedirectResponse
    {
        $tests->update($test, $request->validated());

        return redirect()->route('teacher.tests.show', $test)->with('success', 'Test details updated.');
    }

    public function destroy(Test $test): RedirectResponse
    {
        $test->delete(); // soft delete; the policy only allows it for drafts

        return redirect()->route('teacher.tests.index')->with('success', "Test \"{$test->title}\" deleted.");
    }

    public function publish(Test $test, TestService $tests): RedirectResponse
    {
        // Throws BusinessRuleException (→ back with an error) if a publish rule fails.
        $tests->publish($test);

        return redirect()->route('teacher.tests.show', $test)->with('success', 'Test published. Students can now see it.');
    }

    public function archive(Test $test, TestService $tests): RedirectResponse
    {
        $tests->archive($test);

        return redirect()->route('teacher.tests.show', $test)->with('success', 'Test archived. Students can no longer start it.');
    }
}
