<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Enums\Difficulty;
use App\Http\Controllers\Concerns\ReadsQuestionFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AttachQuestionsRequest;
use App\Http\Requests\Teacher\ReorderTestQuestionsRequest;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tag;
use App\Models\Test;
use App\Services\TestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The test builder: the questions inside one test.
 */
class TestQuestionController extends Controller implements HasMiddleware
{
    use ReadsQuestionFilters;

    /**
     * TestPolicy@update: owner, and the test is still a draft.
     */
    public static function middleware(): array
    {
        return [new Middleware('can:update,test')];
    }

    public function index(Request $request, Test $test): View
    {
        $attached = $test->questions()->with('topic')->get();

        // Plain arrays for the Alpine reorder list, prepared here so the view stays logic-free.
        $items = $attached->map(fn (Question $question): array => [
            'id' => $question->id,
            'body' => Str::limit($question->body, 110),
            'topic' => $question->topic->name,
            'difficulty' => $question->difficulty->label(),
            'marks' => $question->pivot->marks,
            'negative_marks' => $question->pivot->negative_marks,
            'removeUrl' => route('teacher.tests.questions.destroy', [$test, $question]),
        ])->values();

        $filters = $this->questionFilters($request);

        // Picker: active questions NOT already in this test → WHERE NOT EXISTS (... test_question ...).
        // Own page parameter, so paging the picker doesn't clash with anything else on the page.
        $bank = Question::active()
            ->with('topic.subject')
            ->whereDoesntHave('tests', fn (Builder $q) => $q->whereKey($test->id))
            ->filter($filters)
            ->latest('id')
            ->paginate(10, pageName: 'bank_page')
            ->withQueryString();

        return view('teacher.tests.questions', [
            'test' => $test,
            'items' => $items,
            'bank' => $bank,
            'filters' => [...$filters, 'difficulty' => $filters['difficulty']?->value],
            'subjects' => Subject::withSortedTopics()->get(),
            'tags' => Tag::orderBy('name')->pluck('name', 'id'),
            'difficulties' => Difficulty::cases(),
        ]);
    }

    public function store(AttachQuestionsRequest $request, Test $test, TestService $tests): RedirectResponse
    {
        $added = $tests->attachQuestions($test, $request->validated('question_ids'));

        return back()->with(
            $added > 0 ? 'success' : 'error',
            $added > 0 ? "{$added} ".Str::plural('question', $added).' added.' : 'Those questions are already in this test.',
        );
    }

    /**
     * {question} is scoped to the test (->scopeBindings() on the route): a question
     * that is not in this test gives a 404 before we get here.
     */
    public function destroy(Test $test, Question $question, TestService $tests): RedirectResponse
    {
        $tests->detachQuestion($test, $question);

        return back()->with('success', 'Question removed from the test.');
    }

    public function reorder(ReorderTestQuestionsRequest $request, Test $test, TestService $tests): RedirectResponse
    {
        $tests->reorderQuestions($test, $request->validated('question_ids'));

        return back()->with('success', 'Question order saved.');
    }
}
