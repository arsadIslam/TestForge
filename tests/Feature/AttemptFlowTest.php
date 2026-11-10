<?php

use App\Enums\AttemptStatus;
use App\Enums\TestStatus;
use App\Exceptions\BusinessRuleException;
use App\Jobs\EvaluateAttempt;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Support\Facades\Queue;

/*
| start → save answers → submit. Queue::fake() keeps the scoring job out of these
| tests: they check the attempt itself (scoring has its own tests).
*/

beforeEach(function () {
    Queue::fake();
    $this->test = publishedTest();
    $this->student = User::factory()->student()->create();
    [$this->single, $this->multi] = questionsOf($this->test)->all();
});

function start(object $t): Attempt
{
    $t->actingAs($t->student)->post("/student/tests/{$t->test->slug}/attempts")->assertRedirect();

    return Attempt::where('user_id', $t->student->id)->latest('id')->firstOrFail();
}

function saveAnswer(object $t, Attempt $attempt, int $questionId, array $optionIds)
{
    return $t->actingAs($t->student)->putJson("/student/attempts/{$attempt->id}/answers/{$questionId}", ['option_ids' => $optionIds]);
}

// ---------- start ----------

it('starts an attempt with a server-side deadline', function () {
    $attempt = start($this);

    expect($attempt->attempt_no)->toBe(1)
        ->and($attempt->status)->toBe(AttemptStatus::InProgress)
        ->and($attempt->expires_at->equalTo($attempt->started_at->copy()->addMinutes(10)))->toBeTrue();
});

it('resumes the attempt in progress instead of starting a second one', function () {
    $first = start($this);
    $second = start($this);

    expect($second->id)->toBe($first->id)
        ->and(Attempt::where('user_id', $this->student->id)->count())->toBe(1);
});

it('enforces max_attempts', function () {
    $service = app(AttemptService::class);
    $service->submit($service->start($this->student, $this->test));
    $service->submit($service->start($this->student, $this->test)); // max_attempts = 2

    $this->actingAs($this->student)->post("/student/tests/{$this->test->slug}/attempts")
        ->assertSessionHas('error', 'You have used all 2 attempt(s) for this test.');

    expect(Attempt::where('user_id', $this->student->id)->count())->toBe(2);
});

it('refuses to start a test that is not open', function (array $change) {
    $this->test->forceFill($change)->save();

    $this->actingAs($this->student)->post("/student/tests/{$this->test->slug}/attempts")
        ->assertSessionHas('error', 'This test is not open for attempts right now.');
})->with([
    // closures, so now() is evaluated when the test runs (a closure's return value IS the argument)
    'archived' => fn () => ['status' => TestStatus::Archived],
    'not started yet' => fn () => ['starts_at' => now()->addDay()],
    'already closed' => fn () => ['ends_at' => now()->subMinute()],
]);

it('never sends the correct answers to the test-taking page', function () {
    $attempt = start($this);

    $html = $this->actingAs($this->student)->get("/student/attempts/{$attempt->id}")->assertOk()->getContent();

    expect($html)->not->toContain('is_correct')->not->toContain('isCorrect');
});

// ---------- save ----------

it('saves an answer with one row per question (upsert)', function () {
    $attempt = start($this);

    saveAnswer($this, $attempt, $this->single->id, [wrongIds($this->single)[0]])->assertOk();
    saveAnswer($this, $attempt, $this->single->id, [correctIds($this->single)[0]])->assertOk(); // changed mind

    $answers = AttemptAnswer::where('attempt_id', $attempt->id)->get();
    expect($answers)->toHaveCount(1)
        ->and($answers->first()->selectedOptions()->pluck('question_options.id')->all())->toBe(correctIds($this->single));
});

it('saves several options for a multiple-choice question and clears an answer', function () {
    $attempt = start($this);

    saveAnswer($this, $attempt, $this->multi->id, correctIds($this->multi))->assertOk();
    expect(DB::table('attempt_answer_options')->count())->toBe(2);

    saveAnswer($this, $attempt, $this->multi->id, [])->assertOk();
    expect(AttemptAnswer::where('attempt_id', $attempt->id)->exists())->toBeFalse();
});

it('rejects invalid answers', function () {
    $attempt = start($this);
    $other = publishedTest();
    $foreign = questionsOf($other)->first();

    saveAnswer($this, $attempt, $this->single->id, wrongIds($this->single))
        ->assertStatus(422)->assertJson(['message' => 'Only one option can be selected for this question.']);
    saveAnswer($this, $attempt, $this->single->id, [correctIds($this->multi)[0]])
        ->assertStatus(422)->assertJsonValidationErrors('option_ids.0'); // option of another question
    saveAnswer($this, $attempt, $foreign->id, [correctIds($foreign)[0]])
        ->assertStatus(422)->assertJson(['message' => 'This question is not part of your test.']);
});

it('rejects saving after expires_at, whatever the browser timer says', function () {
    $attempt = start($this);

    $this->travel(10)->minutes();
    $this->travel(1)->seconds();

    saveAnswer($this, $attempt, $this->single->id, correctIds($this->single))
        ->assertStatus(422)->assertJson(['message' => 'Time is up. Answers can no longer be changed.']);
    expect(AttemptAnswer::where('attempt_id', $attempt->id)->exists())->toBeFalse();
});

it('does not let another student touch my attempt', function () {
    $attempt = start($this);
    $intruder = User::factory()->student()->create();

    $this->actingAs($intruder)->get("/student/attempts/{$attempt->id}")->assertForbidden();
    $this->actingAs($intruder)->putJson("/student/attempts/{$attempt->id}/answers/{$this->single->id}", ['option_ids' => []])->assertForbidden();
    $this->actingAs($intruder)->post("/student/attempts/{$attempt->id}/submit")->assertForbidden();
});

// ---------- submit ----------

it('submits and queues exactly one scoring job', function () {
    $attempt = start($this);

    $this->actingAs($this->student)->post("/student/attempts/{$attempt->id}/submit")
        ->assertRedirect("/student/attempts/{$attempt->id}/result");

    expect($attempt->fresh()->status)->toBe(AttemptStatus::Submitted);
    Queue::assertPushed(EvaluateAttempt::class, 1);
});

it('can never submit twice, even when both requests loaded the attempt first', function () {
    $attempt = start($this);
    $stale = Attempt::find($attempt->id); // a second request that read it while still in progress
    $service = app(AttemptService::class);

    $service->submit($attempt);

    expect(fn () => $service->submit($stale))->toThrow(BusinessRuleException::class, 'This attempt has already been submitted.');
    $this->actingAs($this->student)->post("/student/attempts/{$attempt->id}/submit")->assertForbidden();
    Queue::assertPushed(EvaluateAttempt::class, 1);
});

it('treats a submit after the deadline as auto-submitted at the deadline', function () {
    $attempt = start($this);
    $this->travel(15)->minutes();

    $this->actingAs($this->student)->post("/student/attempts/{$attempt->id}/submit");

    $attempt->refresh();
    expect($attempt->status)->toBe(AttemptStatus::AutoSubmitted)
        ->and($attempt->submitted_at->equalTo($attempt->expires_at))->toBeTrue();
});

it('auto-submits expired attempts from the scheduled command', function () {
    $expired = start($this);
    $other = User::factory()->student()->create();
    $this->travel(11)->minutes();
    $fresh = app(AttemptService::class)->start($other, $this->test); // started now, not expired

    $this->artisan('attempts:auto-submit')->expectsOutput('Auto-submitted 1 attempt(s).')->assertSuccessful();

    expect($expired->fresh()->status)->toBe(AttemptStatus::AutoSubmitted)
        ->and($fresh->fresh()->status)->toBe(AttemptStatus::InProgress);
    Queue::assertPushed(EvaluateAttempt::class, 1);

    $this->artisan('attempts:auto-submit')->expectsOutput('Auto-submitted 0 attempt(s).');
});
