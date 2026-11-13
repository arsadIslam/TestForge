<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Enums\Difficulty;
use App\Http\Controllers\Concerns\ReadsQuestionFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AttachQuestionsRequest;
use App\Http\Requests\Teacher\GenerateTestQuestionsRequest;
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
        $attached = $test->questions()->with('topic.subject')->get();
        $backToBuilder = route('teacher.tests.questions.index', $test);

        // Plain arrays for the Alpine reorder list, prepared here so the view stays logic-free.
        $items = $attached->map(fn (Question $question): array => [
            'id' => $question->id,
            'body' => Str::limit($question->body, 110),
            'image' => $question->image_url,
            'topic' => $question->topic->name,
            'difficulty' => $question->difficulty->label(),
            'marks' => $question->pivot->marks,
            'negative_marks' => $question->pivot->negative_marks,
            'removeUrl' => route('teacher.tests.questions.destroy', [$test, $question]),
            'swapUrl' => route('teacher.tests.questions.swap', [$test, $question]),
            // Only the author may edit; they come straight back here afterwards.
            'editUrl' => $request->user()->can('update', $question)
                ? route('teacher.questions.edit', [$question, 'return_to' => $backToBuilder])
                : null,
        ])->values();

        // What the (random) selection looks like: counts by difficulty and by topic.
        $composition = [
            'byDifficulty' => $attached->countBy(fn (Question $q): string => $q->difficulty->label())->sortKeys()->all(),
            'byTopic' => $attached->countBy(fn (Question $q): string => $q->topic->subject->name.' › '.$q->topic->name)->sortDesc()->all(),
        ];

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
            'composition' => $composition,
        ]);
    }

    /**
     * "Auto-select questions": random picks by rules, added to (or replacing) the current list.
     */
    public function generate(GenerateTestQuestionsRequest $request, Test $test, TestService $tests): RedirectResponse
    {
        $added = $tests->generate($test, $request->validated('rules'), $request->validated('mode') === 'replace');

        return back()->with('success', "Added {$added} random ".Str::plural('question', $added).'. Swap, remove or reorder them below.');
    }

    /**
     * Replace one question with another random one of the same topic and difficulty.
     */
    public function swap(Test $test, Question $question, TestService $tests): RedirectResponse
    {
        $tests->swapQuestion($test, $question);

        return back()->with('success', 'Swapped in a different question.');
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
