<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\QuestionImportRequest;
use App\Models\Question;
use App\Models\Subject;
use App\Services\QuestionImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk import: upload → preview (nothing saved) → confirm (all-or-nothing) or cancel.
 */
class QuestionImportController extends Controller implements HasMiddleware
{
    public function __construct(private QuestionImportService $imports) {}

    public static function middleware(): array
    {
        return [new Middleware('can:create,'.Question::class)];
    }

    public function create(): View
    {
        return view('teacher.questions.import.create', [
            'columns' => QuestionImportService::columns(),
            'subjects' => Subject::withSortedTopics()->get(),
            'maxRows' => QuestionImportService::MAX_ROWS,
        ]);
    }

    /**
     * A ready-to-fill CSV: the header row plus examples that import cleanly as they are
     * (using a real subject/topic from this site).
     */
    public function template(): StreamedResponse
    {
        $subject = Subject::withSortedTopics()->first();
        $topic = $subject?->topics->first();
        $subjectName = $subject->name ?? 'Mathematics';
        $topicName = $topic->name ?? 'Algebra';

        $rows = [
            [$subjectName, $topicName, 'easy', '1', '0', 'What is 2 + 2?', 'Basic addition.', 'Arithmetic', '', '3', '', '4', '', '5', '', '', '', '', '', '', '', '2'],
            [$subjectName, $topicName, 'medium', '2', '0.5', 'Which of these are prime numbers? Select all that apply.', '2 and 3 have exactly two divisors.', 'Primes|Important', '', '2', '', '3', '', '4', '', '9', '', '', '', '', '', '1|2'],
            [$subjectName, $topicName, 'hard', '3', '1', 'Solve \( x^2 - 5x + 6 = 0 \) — give the smaller root.', '\( (x-2)(x-3) = 0 \)', 'Formula Based', '', '\( x = 2 \)', '', '\( x = 3 \)', '', '\( x = -6 \)', '', '', '', '', '', '', '', '1'],
        ];

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM, so Excel opens the UTF-8 file correctly
            fputcsv($out, QuestionImportService::columns());
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'testforge-questions-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(QuestionImportRequest $request): RedirectResponse
    {
        $uuid = $this->imports->store($request->user(), $request->file('csv'), $request->file('zip'));

        return redirect()->route('teacher.questions.import.show', $uuid);
    }

    public function show(Request $request, string $import): View
    {
        abort_unless($this->imports->exists($request->user(), $import), 404);

        return view('teacher.questions.import.show', [
            'import' => $import,
            'preview' => $this->imports->preview($request->user(), $import),
        ]);
    }

    public function confirm(Request $request, string $import): RedirectResponse
    {
        abort_unless($this->imports->exists($request->user(), $import), 404);

        // Throws BusinessRuleException (→ back with an error) if anything is invalid now.
        $count = $this->imports->import($request->user(), $import);

        return redirect()->route('teacher.questions.index')
            ->with('success', "Imported {$count} ".Str::plural('question', $count).'.');
    }

    public function destroy(Request $request, string $import): RedirectResponse
    {
        $this->imports->discard($request->user(), $import);

        return redirect()->route('teacher.questions.import.create')->with('success', 'Upload discarded.');
    }
}
