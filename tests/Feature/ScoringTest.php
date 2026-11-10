<?php

use App\Enums\AttemptStatus;
use App\Events\ResultPublished;
use App\Models\Attempt;
use App\Models\Result;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\ScoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/*
| End-to-end scoring: answers saved through AttemptService, submitted, scored by the
| EvaluateAttempt job (the sync queue in phpunit.xml runs it straight away).
| Default test: Q1 single +2/−0.5, Q2 multiple +3/−1 (two correct options), pass mark 1.
*/

beforeEach(function () {
    $this->test = publishedTest();
    [$this->single, $this->multi] = questionsOf($this->test)->all();
    $this->service = app(AttemptService::class);
});

/**
 * @param  array<int, list<int>>  $answers  question id => selected option ids
 */
function takeTest(object $t, array $answers, int $minutes = 3): Result
{
    $attempt = $t->service->start(User::factory()->student()->create(), $t->test);
    foreach ($answers as $questionId => $optionIds) {
        $question = $questionId === $t->single->id ? $t->single : $t->multi;
        $t->service->saveAnswer($attempt, $question, $optionIds);
    }
    test()->travel($minutes)->minutes();
    $t->service->submit($attempt);

    return Result::where('attempt_id', $attempt->id)->sole();
}

it('scores a correct single-choice answer', function () {
    $result = takeTest($this, [$this->single->id => correctIds($this->single)]);

    expect($result->score)->toBe('2.00')
        ->and([$result->correct_count, $result->wrong_count, $result->skipped_count])->toBe([1, 0, 1])
        ->and($result->accuracy)->toBe('100.00')
        ->and($result->is_passed)->toBeTrue();
});

it('applies negative marks to a wrong single-choice answer', function () {
    $result = takeTest($this, [$this->single->id => [wrongIds($this->single)[0]]]);

    expect($result->score)->toBe('-0.50')->and($result->is_passed)->toBeFalse();
});

it('only scores a multiple-choice answer when the selected set is exactly right', function (string $case, string $score) {
    $selection = match ($case) {
        'exact' => correctIds($this->multi),
        'partial' => [correctIds($this->multi)[0]],
        'superset' => [...correctIds($this->multi), wrongIds($this->multi)[0]],
    };

    expect(takeTest($this, [$this->multi->id => $selection])->score)->toBe($score);
})->with([
    'exact set → +3' => ['exact', '3.00'],
    'one missing → −1 (no partial marks)' => ['partial', '-1.00'],
    'one extra → −1' => ['superset', '-1.00'],
]);

it('scores unanswered questions as 0 and adds everything up', function () {
    $result = takeTest($this, [
        $this->single->id => [wrongIds($this->single)[0]], // −0.5
        $this->multi->id => correctIds($this->multi),       // +3
    ], minutes: 4);

    expect($result->score)->toBe('2.50')
        ->and([$result->correct_count, $result->wrong_count, $result->skipped_count])->toBe([1, 1, 0])
        ->and($result->accuracy)->toBe('50.00')
        ->and($result->time_taken_seconds)->toBe(240);

    $blank = takeTest($this, []);
    expect([$blank->score, $blank->skipped_count, $blank->accuracy])->toBe(['0.00', 2, '0.00']);
});

it('uses the marks copied into the test, not the question\'s current marks', function () {
    $this->single->update(['marks' => 50, 'negative_marks' => 10]); // edited after publishing

    expect(takeTest($this, [$this->single->id => correctIds($this->single)])->score)->toBe('2.00')
        ->and(takeTest($this, [$this->single->id => [wrongIds($this->single)[0]]])->score)->toBe('-0.50');
});

it('stores is_correct and marks_awarded on each answer and marks the attempt evaluated', function () {
    $result = takeTest($this, [$this->single->id => correctIds($this->single), $this->multi->id => [correctIds($this->multi)[0]]]);

    $answers = DB::table('attempt_answers')->where('attempt_id', $result->attempt_id)->orderBy('question_id')->get();
    expect($answers->pluck('is_correct')->map(fn ($v) => (bool) $v)->all())->toBe([true, false])
        ->and($answers->pluck('marks_awarded')->all())->toBe(['2.00', '-1.00'])
        ->and(Attempt::find($result->attempt_id)->status)->toBe(AttemptStatus::Evaluated);
});

it('is idempotent: scoring the same attempt again never creates a second result', function () {
    Event::fake([ResultPublished::class]);
    $result = takeTest($this, [$this->single->id => correctIds($this->single)]);

    $again = app(ScoringService::class)->evaluate(Attempt::find($result->attempt_id));

    expect($again->id)->toBe($result->id)
        ->and(Result::where('attempt_id', $result->attempt_id)->count())->toBe(1);
    Event::assertDispatchedTimes(ResultPublished::class, 1);
});

it('refuses to score an attempt that is still in progress', function () {
    $attempt = $this->service->start(User::factory()->student()->create(), $this->test);

    expect(fn () => app(ScoringService::class)->evaluate($attempt))->toThrow(LogicException::class);
});
