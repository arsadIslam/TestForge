<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Http\Controllers\Concerns\ReadsQuestionFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreQuestionRequest;
use App\Http\Requests\Teacher\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tag;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class QuestionController extends Controller implements HasMiddleware
{
    use ReadsQuestionFilters;

    /**
     * Every action is authorized against QuestionPolicy before it runs.
     * 'question' refers to the {question} route parameter.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Question::class, only: ['index']),
            new Middleware('can:create,'.Question::class, only: ['create', 'store']),
            new Middleware('can:view,question', only: ['show']),
            new Middleware('can:update,question', only: ['edit', 'update']),
            new Middleware('can:delete,question', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $this->questionFilters($request);

        $questions = Question::query()
            ->with(['topic.subject', 'tags', 'creator:id,name']) // 4 queries for the page, however many rows
            ->filter($filters)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('teacher.questions.index', [
            'questions' => $questions,
            'subjects' => Subject::withSortedTopics()->get(),
            'tags' => Tag::orderBy('name')->pluck('name', 'id'),
            'difficulties' => Difficulty::cases(),
            'filters' => [...$filters, 'difficulty' => $filters['difficulty']?->value],
        ]);
    }

    public function create(): View
    {
        $question = new Question([
            'type' => QuestionType::Single,
            'difficulty' => Difficulty::Medium,
            'marks' => 1,
            'negative_marks' => 0,
            'is_active' => true,
        ]);

        return view('teacher.questions.create', $this->formData($question));
    }

    public function store(StoreQuestionRequest $request, QuestionService $questions): RedirectResponse
    {
        $question = $questions->create($request->user(), $request->validated());

        return redirect()->route('teacher.questions.show', $question)
            ->with('success', 'Question created.');
    }

    public function show(Question $question): View
    {
        $question->load(['topic.subject', 'options', 'tags', 'creator:id,name'])->loadCount('tests');

        return view('teacher.questions.show', [
            'question' => $question,
            'locked' => $question->isLocked(),
        ]);
    }

    public function edit(Question $question): View
    {
        $question->load(['options', 'tags']);

        return view('teacher.questions.edit', $this->formData($question));
    }

    public function update(UpdateQuestionRequest $request, Question $question, QuestionService $questions): RedirectResponse
    {
        $questions->update($question, $request->validated());

        return redirect()->route('teacher.questions.show', $question)
            ->with('success', 'Question updated.');
    }

    public function destroy(Question $question, QuestionService $questions): RedirectResponse
    {
        // Throws BusinessRuleException (→ redirect back with an error) if a test uses it.
        $questions->delete($question);

        return redirect()->route('teacher.questions.index')
            ->with('success', 'Question deleted.');
    }

    /**
     * Everything the create/edit form needs, prepared here so the Blade view stays logic-free.
     *
     * @return array<string, mixed>
     */
    private function formData(Question $question): array
    {
        $options = $question->exists
            ? $question->options->map(fn ($option): array => [
                'id' => $option->id,
                'text' => $option->option_text,
                'is_correct' => $option->is_correct,
            ])->all()
            : array_fill(0, 4, ['id' => null, 'text' => '', 'is_correct' => false]);

        return [
            'question' => $question,
            'locked' => $question->exists && $question->isLocked(),
            'options' => $options,
            'tagString' => $question->exists ? $question->tags->pluck('name')->implode(', ') : '',
            'subjects' => Subject::withSortedTopics()->get(),
            'existingTags' => Tag::orderBy('name')->pluck('name'),
            'types' => QuestionType::cases(),
            'difficulties' => Difficulty::cases(),
            'minOptions' => StoreQuestionRequest::MIN_OPTIONS,
            'maxOptions' => StoreQuestionRequest::MAX_OPTIONS,
        ];
    }
}
