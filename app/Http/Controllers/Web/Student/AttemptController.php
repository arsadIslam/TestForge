<?php

namespace App\Http\Controllers\Web\Student;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveAnswerRequest;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use App\Services\AttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AttemptController extends Controller implements HasMiddleware
{
    /**
     * AttemptPolicy: only the student who owns the attempt; saving/submitting only while in progress.
     * (store has no attempt yet — AttemptService decides whether the student may start.)
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:view,attempt', only: ['show']),
            new Middleware('can:saveAnswer,attempt', only: ['saveAnswer']),
            new Middleware('can:submit,attempt', only: ['submit']),
        ];
    }

    /**
     * POST /student/tests/{test:slug}/attempts — start (or resume) an attempt.
     */
    public function store(Request $request, Test $test, AttemptService $attempts): RedirectResponse
    {
        $attempt = $attempts->start($request->user(), $test, $request->ip());

        return redirect()->route('student.attempts.show', $attempt);
    }

    /**
     * The test-taking screen.
     */
    public function show(Attempt $attempt, AttemptService $attempts): View|RedirectResponse
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            return redirect()->route('student.tests.index')->with('error', 'That attempt has already been submitted.');
        }

        if ($attempt->expires_at->isPast()) {
            $attempts->submit($attempt); // closes it as auto-submitted

            return redirect()->route('student.tests.index')->with('error', 'Time ran out, so your attempt was submitted automatically.');
        }

        $attempt->load(['test', 'answers.selectedOptions:id']);

        // Plain arrays for the Alpine component. is_correct is deliberately NOT included:
        // anything sent to the browser can be read with "View source".
        $questions = $attempts->questionsFor($attempt)->map(fn (Question $question): array => [
            'id' => $question->id,
            'type' => $question->type->value,
            'body' => $question->body,
            'image' => $question->image_url,
            'marks' => $question->pivot->marks,
            'negativeMarks' => $question->pivot->negative_marks,
            'saveUrl' => route('student.attempts.answers.save', [$attempt, $question]),
            'options' => $question->options->map(fn ($option): array => [
                'id' => $option->id,
                'text' => $option->option_text,
                'image' => $option->image_url, // random file name: never hints at the answer
            ])->values(),
            'hasOptionImages' => $question->options->contains(fn ($option): bool => $option->image_path !== null),
        ])->values();

        $answers = $attempt->answers->mapWithKeys(fn ($answer): array => [
            $answer->question_id => $answer->selectedOptions->pluck('id')->all(),
        ]);

        return view('student.attempts.show', [
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => (object) $answers->all(), // {} not [] in JSON when empty
            // Seconds left by the SERVER's clock, so a wrong clock on the student's PC doesn't matter.
            'secondsLeft' => (int) max(0, now()->diffInSeconds($attempt->expires_at)),
        ]);
    }

    /**
     * PUT /student/attempts/{attempt}/answers/{question} — called by the auto-save fetch.
     * Errors come back as 422 JSON (BusinessRuleException / validation), never a redirect.
     */
    public function saveAnswer(SaveAnswerRequest $request, Attempt $attempt, Question $question, AttemptService $attempts): JsonResponse
    {
        $attempts->saveAnswer($attempt, $question, $request->validated('option_ids'));

        return response()->json(['saved' => true]);
    }

    public function submit(Attempt $attempt, AttemptService $attempts): RedirectResponse
    {
        $attempt = $attempts->submit($attempt);

        $message = $attempt->status === AttemptStatus::AutoSubmitted
            ? 'Time was up, so your attempt was submitted automatically.'
            : 'Your test has been submitted.';

        // The result page shows "being marked" until the EvaluateAttempt job has run.
        return redirect()->route('student.attempts.result', $attempt)->with('success', $message);
    }
}
