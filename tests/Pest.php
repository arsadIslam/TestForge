<?php

use App\Enums\QuestionType;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Result;
use App\Models\Test;
use App\Models\User;
use App\Services\TestService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
| Feature tests boot the app and run against the MySQL testing database
| (phpunit.xml), wrapped in a transaction that is rolled back after each test.
| Unit tests (tests/Unit) are plain PHPUnit — no app, no database.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * A published test whose answers we know exactly. Built through TestService,
 * the same code the teacher UI uses (pivot marks copied, total_marks calculated).
 *
 * Default: Q1 single choice (+2 / −0.5), options [correct, wrong, wrong]
 *          Q2 multiple choice (+3 / −1), options [correct, correct, wrong, wrong]
 *
 * @param  list<array{type: string, marks: float, negative: float, options: list<bool>}>|null  $spec
 */
function publishedTest(?array $spec = null, array $attributes = []): Test
{
    $spec ??= [
        ['type' => 'single', 'marks' => 2, 'negative' => 0.5, 'options' => [true, false, false]],
        ['type' => 'multiple', 'marks' => 3, 'negative' => 1, 'options' => [true, true, false, false]],
    ];

    $teacher = User::factory()->teacher()->create();

    $questionIds = collect($spec)->map(function (array $q) use ($teacher): int {
        $question = Question::factory()->for($teacher, 'creator')->create([
            'type' => QuestionType::from($q['type']),
            'marks' => $q['marks'],
            'negative_marks' => $q['negative'],
        ]);

        foreach ($q['options'] as $i => $isCorrect) {
            $question->options()->create(['option_text' => "Option {$i}", 'is_correct' => $isCorrect, 'sort_order' => $i + 1]);
        }

        return $question->id;
    })->all();

    $service = app(TestService::class);
    $test = $service->create($teacher, array_merge([
        'title' => 'Pest Test '.uniqid(),
        'duration_minutes' => 10,
        'pass_marks' => 1,
        'max_attempts' => 2,
        'shuffle_questions' => false,
    ], $attributes));
    $service->attachQuestions($test, $questionIds);
    $service->publish($test);

    return $test->fresh();
}

/**
 * The test's questions in order, with options loaded.
 *
 * @return Collection<int, Question>
 */
function questionsOf(Test $test)
{
    return $test->questions()->with('options')->get();
}

/**
 * @return list<int>
 */
function correctIds(Question $question): array
{
    return $question->options->where('is_correct', true)->pluck('id')->values()->all();
}

/**
 * @return list<int>
 */
function wrongIds(Question $question): array
{
    return $question->options->where('is_correct', false)->pluck('id')->values()->all();
}

/**
 * An evaluated attempt + result with a chosen score/time (for leaderboard tests).
 */
function resultFor(Test $test, User $student, float $score, int $seconds, int $attemptNo = 1): Result
{
    $attempt = Attempt::factory()->evaluated()->for($test)->for($student, 'student')->create(['attempt_no' => $attemptNo]);

    return Result::factory()->create([
        'attempt_id' => $attempt->id,
        'score' => $score,
        'time_taken_seconds' => $seconds,
    ]);
}
