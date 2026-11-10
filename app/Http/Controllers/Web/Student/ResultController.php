<?php

namespace App\Http\Controllers\Web\Student;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Question;
use App\Services\AttemptService;
use App\Services\LeaderboardService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ResultController extends Controller implements HasMiddleware
{
    /**
     * AttemptPolicy@view — inside the student area this means: only your own attempt.
     */
    public static function middleware(): array
    {
        return [new Middleware('can:view,attempt')];
    }

    public function show(Attempt $attempt, AttemptService $attempts, LeaderboardService $leaderboard): View
    {
        $attempt->load(['test', 'result']);

        // Submitted but the queue hasn't scored it yet → a "being marked" page that refreshes itself.
        if ($attempt->status !== AttemptStatus::Evaluated) {
            return view('student.results.pending', ['attempt' => $attempt]);
        }

        $attempt->load('answers.selectedOptions:id');
        $answers = $attempt->answers->keyBy('question_id');

        // Same order the student saw (shuffle is seeded per attempt), now WITH correct answers.
        $review = $attempts->questionsFor($attempt)->map(function (Question $question) use ($answers): array {
            $answer = $answers->get($question->id);

            return [
                'question' => $question,
                'selected' => $answer?->selectedOptions->pluck('id')->all() ?? [],
                'status' => match (true) {
                    $answer === null => 'skipped',
                    $answer->is_correct => 'correct',
                    default => 'wrong',
                },
                'marks' => $answer?->marks_awarded ?? '0.00',
            ];
        });

        return view('student.results.show', [
            'attempt' => $attempt,
            'result' => $attempt->result,
            'review' => $review,
            'myRank' => $leaderboard->rankOf($attempt->test, $attempt->user_id), // best attempt's rank
        ]);
    }
}
