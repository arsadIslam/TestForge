<?php

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Support\Facades\Queue;

/*
| Single vs multiple choice is DERIVED from the correct options, never chosen:
| 1 correct → single (students pick one), 2+ correct → multiple (students pick all correct).
*/

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create();
    $this->topic = Topic::factory()->create();
});

function typePayload(object $t, array $correct, array $override = []): array
{
    return array_replace([
        'topic_id' => $t->topic->id, 'difficulty' => 'easy', 'body' => 'Pick the primes', 'marks' => '1',
        'negative_marks' => '0', 'is_active' => '1', 'tags' => '',
        'options' => collect(['2', '3', '4', '9'])->map(fn ($text, $i) => [
            'id' => '', 'text' => $text, 'is_correct' => in_array($i, $correct, true) ? '1' : '0',
        ])->all(),
    ], $override);
}

it('makes a question single choice when exactly one option is correct', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', typePayload($this, [0]))->assertSessionHasNoErrors();

    expect(Question::latest('id')->first()->type)->toBe(QuestionType::Single);
});

it('makes a question multiple choice when two or more options are correct', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', typePayload($this, [0, 1]))->assertSessionHasNoErrors();

    expect(Question::latest('id')->first()->type)->toBe(QuestionType::Multiple);
});

it('ignores a type sent in the request', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', typePayload($this, [0], ['type' => 'multiple']))
        ->assertSessionHasNoErrors();

    expect(Question::latest('id')->first()->type)->toBe(QuestionType::Single);
});

it('needs at least one correct option', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', typePayload($this, []))
        ->assertSessionHasErrors(['options' => 'Mark at least one option as correct.']);
});

it('switches type when the number of correct options changes', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', typePayload($this, [0]));
    $q = Question::with('options')->latest('id')->first();

    $withIds = fn (array $correct) => collect($q->options)->map(fn ($o, $i) => [
        'id' => $o->id, 'text' => $o->option_text, 'is_correct' => in_array($i, $correct, true) ? '1' : '0',
    ])->all();

    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", typePayload($this, [], ['options' => $withIds([0, 1])]));
    expect($q->fresh()->type)->toBe(QuestionType::Multiple);

    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", typePayload($this, [], ['options' => $withIds([1])]));
    expect($q->fresh()->type)->toBe(QuestionType::Single);
});

it('never changes the type of a locked question', function () {
    $test = publishedTest(); // Q1 single (1 correct), Q2 multiple (2 correct)
    $q = questionsOf($test)->first();
    $q->forceFill(['created_by' => $this->teacher->id])->save();

    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", typePayload($this, [0, 1, 2], ['topic_id' => $q->topic_id]))
        ->assertRedirect("/teacher/questions/{$q->id}");

    expect($q->fresh()->type)->toBe(QuestionType::Single)
        ->and($q->options()->where('is_correct', true)->count())->toBe(1);
});

it('tells the test screen which questions allow several answers, and the server enforces it', function () {
    Queue::fake();
    $test = publishedTest();
    [$single, $multi] = questionsOf($test)->all();
    $student = User::factory()->student()->create();
    $attempt = app(AttemptService::class)->start($student, $test);

    $types = $this->actingAs($student)->get("/student/attempts/{$attempt->id}")->assertOk()
        ->assertSee('Select one answer')->viewData('questions')->pluck('type', 'id');

    expect($types[$single->id])->toBe('single')->and($types[$multi->id])->toBe('multiple');

    $this->actingAs($student)->putJson("/student/attempts/{$attempt->id}/answers/{$single->id}", [
        'option_ids' => $single->options->pluck('id')->take(2)->all(),
    ])->assertStatus(422);
});
