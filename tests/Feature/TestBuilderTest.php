<?php

use App\Enums\TestStatus;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create();
    $this->actingAs($this->teacher)->post('/teacher/tests', [
        'title' => 'Algebra Weekly', 'duration_minutes' => 20, 'pass_marks' => 1,
        'max_attempts' => 1, 'shuffle_questions' => '0',
    ]);
    $this->test = Test::latest('id')->first();
});

function addQuestions(object $t, int $count = 1): void
{
    $ids = Question::factory()->count($count)->withOptions()->create(['created_by' => $t->teacher->id])->pluck('id')->all();
    $t->actingAs($t->teacher)->post("/teacher/tests/{$t->test->id}/questions", ['question_ids' => $ids]);
}

it('will not publish a test without questions', function () {
    $this->actingAs($this->teacher)->patch("/teacher/tests/{$this->test->id}/publish")
        ->assertSessionHas('error', 'Add at least one question before publishing.');

    expect($this->test->fresh()->status)->toBe(TestStatus::Draft);
});

it('keeps total_marks equal to the sum of the copied pivot marks', function () {
    addQuestions($this, 3);

    $pivotSum = DB::table('test_question')->where('test_id', $this->test->id)->sum('marks');
    expect((float) $this->test->fresh()->total_marks)->toEqual((float) $pivotSum);
});

it('publishes, then freezes the test and its questions\' answer keys', function () {
    addQuestions($this, 2);

    $this->actingAs($this->teacher)->patch("/teacher/tests/{$this->test->id}/publish")->assertSessionHas('success');

    expect($this->test->fresh()->status)->toBe(TestStatus::Published)
        ->and($this->test->questions()->first()->isLocked())->toBeTrue();
    $this->actingAs($this->teacher)->get("/teacher/tests/{$this->test->id}/edit")->assertForbidden();
    $this->actingAs($this->teacher)->get("/teacher/tests/{$this->test->id}/questions")->assertForbidden();
});

it('will not publish when the pass mark is above the total', function () {
    addQuestions($this);
    $this->test->update(['pass_marks' => 999]);

    $this->actingAs($this->teacher)->patch("/teacher/tests/{$this->test->id}/publish")
        ->assertSessionHas('error', fn (string $m) => str_contains($m, 'cannot be higher than the total marks'));
});

it('lets only the owner manage a test', function () {
    $other = User::factory()->teacher()->create();

    $this->actingAs($other)->get("/teacher/tests/{$this->test->id}")->assertForbidden();
    $this->actingAs($other)->patch("/teacher/tests/{$this->test->id}/publish")->assertForbidden();
});

it('refuses to delete a question that a test uses', function () {
    addQuestions($this);
    $question = $this->test->questions()->first();

    $this->actingAs($this->teacher)->delete("/teacher/questions/{$question->id}")
        ->assertSessionHas('error', 'This question is used in a test. Remove it from the test before deleting it.');

    expect($question->fresh()->trashed())->toBeFalse();
});
