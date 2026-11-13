<?php

use App\Enums\Difficulty;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
| "Auto-select questions": random picks by rules, then the teacher edits the result
| (swap, remove, reorder, edit-and-come-back).
*/

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create();
    $this->maths = Subject::create(['name' => 'Mathematics', 'slug' => 'mathematics']);
    $this->algebra = $this->maths->topics()->create(['name' => 'Algebra', 'slug' => 'algebra']);
    $this->geometry = $this->maths->topics()->create(['name' => 'Geometry', 'slug' => 'geometry']);

    // 6 easy + 3 hard algebra, 4 easy geometry, 2 inactive hard algebra
    $make = fn ($topic, $difficulty, $count, $active = true) => Question::factory()->count($count)->withOptions()
        ->create(['topic_id' => $topic->id, 'difficulty' => $difficulty, 'is_active' => $active, 'created_by' => $this->teacher->id]);
    $make($this->algebra, Difficulty::Easy, 6);
    $make($this->algebra, Difficulty::Hard, 3);
    $make($this->geometry, Difficulty::Easy, 4);
    $make($this->algebra, Difficulty::Hard, 2, false);

    $this->actingAs($this->teacher)->post('/teacher/tests', [
        'title' => 'Random Mock', 'duration_minutes' => 20, 'pass_marks' => 0, 'max_attempts' => 1, 'shuffle_questions' => '0',
    ]);
    $this->test = Test::latest('id')->first();
});

function generate(object $t, array $rules, string $mode = 'add')
{
    return $t->actingAs($t->teacher)->post("/teacher/tests/{$t->test->id}/questions/generate", ['rules' => $rules, 'mode' => $mode]);
}

function picked(Test $test)
{
    return Question::whereIn('id', DB::table('test_question')->where('test_id', $test->id)->orderBy('sort_order')->pluck('question_id'))->get();
}

it('picks random questions matching each rule', function () {
    generate($this, [
        ['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'hard', 'count' => 3],
        ['subject_id' => $this->maths->id, 'topic_id' => $this->geometry->id, 'difficulty' => '', 'count' => 2],
    ])->assertSessionHas('success', 'Added 5 random questions. Swap, remove or reorder them below.');

    $questions = picked($this->test);
    expect($questions)->toHaveCount(5)
        ->and($questions->where('topic_id', $this->algebra->id)->where('difficulty', Difficulty::Hard))->toHaveCount(3)
        ->and($questions->where('topic_id', $this->geometry->id))->toHaveCount(2)
        ->and($questions->every->is_active)->toBeTrue() // the 2 inactive hard ones are never picked
        ->and(DB::table('test_question')->where('test_id', $this->test->id)->pluck('sort_order')->sort()->values()->all())->toBe([1, 2, 3, 4, 5])
        ->and((float) $this->test->fresh()->total_marks)->toEqual((float) DB::table('test_question')->where('test_id', $this->test->id)->sum('marks'));
});

it('never picks the same question twice, across rules and with what is already in the test', function () {
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'easy', 'count' => 4]]);
    generate($this, [
        ['subject_id' => $this->maths->id, 'topic_id' => '', 'difficulty' => 'easy', 'count' => 3],   // "any topic" overlaps rule 2
        ['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'easy', 'count' => 2],
    ])->assertSessionHas('success');

    $ids = DB::table('test_question')->where('test_id', $this->test->id)->pluck('question_id');
    expect($ids)->toHaveCount(9)->and($ids->unique())->toHaveCount(9);
});

it('changes nothing when a rule cannot be filled, and says what is available', function () {
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->geometry->id, 'difficulty' => '', 'count' => 2]]);
    $before = DB::table('test_question')->where('test_id', $this->test->id)->pluck('question_id')->all();

    generate($this, [
        ['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'easy', 'count' => 2],
        ['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'hard', 'count' => 5],
    ], 'replace')->assertSessionHas('error', fn ($m) => str_contains($m, 'Mathematics › Algebra · hard: asked 5, only 3 available')
        && str_contains($m, 'Nothing was changed'));

    // even "replace" rolled back
    expect(DB::table('test_question')->where('test_id', $this->test->id)->pluck('question_id')->all())->toBe($before);
});

it('replaces the current questions in replace mode', function () {
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->geometry->id, 'difficulty' => '', 'count' => 4]]);
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'hard', 'count' => 3]], 'replace');

    expect(picked($this->test)->pluck('topic_id')->unique()->all())->toBe([$this->algebra->id])
        ->and(picked($this->test))->toHaveCount(3);
});

it('validates the rules', function () {
    $other = Subject::create(['name' => 'Physics', 'slug' => 'physics']);

    generate($this, [['subject_id' => $other->id, 'topic_id' => $this->algebra->id, 'count' => 1]])
        ->assertSessionHasErrors('rules.0.topic_id');
    generate($this, [['subject_id' => $this->maths->id, 'count' => 0]])->assertSessionHasErrors('rules.0.count');
    generate($this, [['subject_id' => $this->maths->id, 'difficulty' => 'extreme', 'count' => 1]])->assertSessionHasErrors('rules.0.difficulty');
    generate($this, array_fill(0, 11, ['subject_id' => $this->maths->id, 'count' => 1]))->assertSessionHasErrors('rules');
    generate($this, array_fill(0, 5, ['subject_id' => $this->maths->id, 'count' => 50]))->assertSessionHasErrors('rules');
});

it('swaps a question for another of the same topic and difficulty, in the same position', function () {
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'easy', 'count' => 3]]);
    $second = DB::table('test_question')->where('test_id', $this->test->id)->where('sort_order', 2)->value('question_id');

    $this->actingAs($this->teacher)->post("/teacher/tests/{$this->test->id}/questions/{$second}/swap")
        ->assertSessionHas('success', 'Swapped in a different question.');

    $now = DB::table('test_question')->where('test_id', $this->test->id)->where('sort_order', 2)->first();
    $new = Question::find($now->question_id);
    expect($now->question_id)->not->toBe($second)
        ->and($new->topic_id)->toBe($this->algebra->id)
        ->and($new->difficulty)->toBe(Difficulty::Easy)
        ->and($now->marks)->toBe($new->marks)
        ->and(DB::table('test_question')->where('test_id', $this->test->id)->count())->toBe(3);
});

it('explains when there is nothing to swap in', function () {
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->algebra->id, 'difficulty' => 'hard', 'count' => 3]]); // all 3 active hard
    $first = DB::table('test_question')->where('test_id', $this->test->id)->value('question_id');

    $this->actingAs($this->teacher)->post("/teacher/tests/{$this->test->id}/questions/{$first}/swap")
        ->assertSessionHas('error', 'There is no other active hard question in this topic to swap in.');
});

it('refuses to generate or swap on a published test or someone else\'s test', function () {
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->geometry->id, 'count' => 1]]);
    $question = DB::table('test_question')->where('test_id', $this->test->id)->value('question_id');
    $other = User::factory()->teacher()->create();

    $this->actingAs($other)->post("/teacher/tests/{$this->test->id}/questions/generate", ['rules' => [['subject_id' => $this->maths->id, 'count' => 1]], 'mode' => 'add'])->assertForbidden();
    $this->actingAs($other)->post("/teacher/tests/{$this->test->id}/questions/{$question}/swap")->assertForbidden();

    $this->actingAs($this->teacher)->patch("/teacher/tests/{$this->test->id}/publish");
    generate($this, [['subject_id' => $this->maths->id, 'count' => 1]])->assertForbidden();
});

it('shows the composition and lets the author edit a question and come straight back', function () {
    generate($this, [['subject_id' => $this->maths->id, 'topic_id' => $this->geometry->id, 'difficulty' => 'easy', 'count' => 2]]);
    $builder = "/teacher/tests/{$this->test->id}/questions";

    $page = $this->actingAs($this->teacher)->get($builder)->assertOk()->assertSee('Easy: 2')->assertSee('Mathematics › Geometry: 2');
    $item = $page->viewData('items')->first();
    expect($item['editUrl'])->toContain('return_to=');

    $q = Question::with('options')->find($item['id']);
    $payload = [
        'topic_id' => $q->topic_id, 'difficulty' => 'easy', 'body' => 'Edited from the builder', 'marks' => '1', 'negative_marks' => '0',
        'is_active' => '1', 'tags' => '',
        'options' => $q->options->map(fn ($o) => ['id' => $o->id, 'text' => $o->option_text, 'is_correct' => $o->is_correct ? '1' : '0'])->all(),
    ];

    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", [...$payload, 'return_to' => url($builder)])->assertRedirect(url($builder));
    // an outside URL is ignored (no open redirect)
    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", [...$payload, 'return_to' => 'https://evil.example/phish'])
        ->assertRedirect("/teacher/questions/{$q->id}");
});
