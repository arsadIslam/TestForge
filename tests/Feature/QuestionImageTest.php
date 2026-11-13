<?php

use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\QuestionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->teacher = User::factory()->teacher()->create();
    $this->topic = Topic::factory()->create();
});

/**
 * A valid single-choice question; override any field.
 */
function imagePayload(object $t, array $override = []): array
{
    return array_replace([
        'topic_id' => $t->topic->id, 'type' => 'single', 'difficulty' => 'easy',
        'body' => 'Which shape is shown?', 'explanation' => '', 'marks' => '2', 'negative_marks' => '0',
        'is_active' => '1', 'tags' => '',
        'options' => [
            ['id' => '', 'text' => 'Triangle', 'is_correct' => '1'],
            ['id' => '', 'text' => 'Square', 'is_correct' => '0'],
        ],
    ], $override);
}

function storedFiles(): array
{
    return Storage::disk('public')->allFiles('questions');
}

// ---------- create ----------

it('stores a question image and option images under random names', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', imagePayload($this, [
        'image' => UploadedFile::fake()->image('diagram.png', 400, 300),
        'options' => [
            ['id' => '', 'text' => '', 'is_correct' => '1', 'image' => UploadedFile::fake()->image('q1_correct.png', 100, 100)],
            ['id' => '', 'text' => 'Square', 'is_correct' => '0'],
        ],
    ]))->assertSessionHasNoErrors();

    $question = Question::with('options')->latest('id')->first();

    expect($question->image_path)->toStartWith('questions/')
        ->and($question->options[0]->image_path)->toStartWith('questions/')
        ->and($question->options[0]->option_text)->toBeNull()          // image-only option
        ->and($question->options[1]->image_path)->toBeNull()
        ->and(storedFiles())->toHaveCount(2)
        // the original name ("q1_correct") must never reach the browser
        ->and(implode(' ', storedFiles()))->not->toContain('correct')->not->toContain('diagram');

    $this->actingAs($this->teacher)->get("/teacher/questions/{$question->id}")
        ->assertSee($question->image_url, false)
        ->assertSee($question->options[0]->image_url, false);
});

it('rejects files that are not small JPG/PNG/WebP images', function (Closure $file, string $message) {
    $this->actingAs($this->teacher)->post('/teacher/questions', imagePayload($this, ['image' => $file()]))
        ->assertSessionHasErrors(['image' => $message]);

    expect(storedFiles())->toBeEmpty();
})->with([
    'SVG (could carry a script)' => [fn () => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'The image must be a JPG, PNG or WebP file.'],
    'PDF' => [fn () => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'), 'The image must be a JPG, PNG or WebP file.'],
    'over 2 MB' => [fn () => UploadedFile::fake()->image('big.jpg', 100, 100)->size(2100), 'The image must be 2 MB or smaller.'],
    'over 4000 px' => [fn () => UploadedFile::fake()->image('wide.png', 4100, 10), 'The image can be at most 4000 × 4000 pixels.'],
]);

it('needs text or an image on every option, and checks duplicates only between texts', function () {
    $post = fn (array $options) => $this->actingAs($this->teacher)->post('/teacher/questions', imagePayload($this, ['options' => $options]));

    $post([['text' => 'Triangle', 'is_correct' => '1'], ['text' => '', 'is_correct' => '0']])
        ->assertSessionHasErrors(['options.1.text' => 'Option 2 needs text or an image.']);

    $post([['text' => 'Same', 'is_correct' => '1'], ['text' => 'same', 'is_correct' => '0']])
        ->assertSessionHasErrors(['options.1.text' => 'Options 1 and 2 have the same text.']);

    // two image-only options are not "duplicates"
    $post([
        ['text' => '', 'is_correct' => '1', 'image' => UploadedFile::fake()->image('a.png')],
        ['text' => '', 'is_correct' => '0', 'image' => UploadedFile::fake()->image('b.png')],
    ])->assertSessionHasNoErrors();
});

// ---------- update ----------

it('replaces and removes images, deleting the old files', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', imagePayload($this, [
        'image' => UploadedFile::fake()->image('one.png'),
        'options' => [
            ['id' => '', 'text' => 'A', 'is_correct' => '1', 'image' => UploadedFile::fake()->image('oa.png')],
            ['id' => '', 'text' => 'B', 'is_correct' => '0', 'image' => UploadedFile::fake()->image('ob.png')],
            ['id' => '', 'text' => 'C', 'is_correct' => '0'],
        ],
    ]));
    $q = Question::with('options')->latest('id')->first();
    [$a, $b, $c] = $q->options->all();
    $oldQuestionImage = $q->image_path;

    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", imagePayload($this, [
        'image' => UploadedFile::fake()->image('two.png'),                                    // replace
        'options' => [
            ['id' => $a->id, 'text' => 'A', 'is_correct' => '1', 'remove_image' => '1'],     // remove
            ['id' => $c->id, 'text' => 'C', 'is_correct' => '0', 'image' => UploadedFile::fake()->image('oc.png')], // add
            // option B dropped entirely → its image file goes too
        ],
    ]))->assertSessionHasNoErrors();

    $q->refresh()->load('options');
    expect($q->image_path)->not->toBe($oldQuestionImage)
        ->and(Storage::disk('public')->exists($oldQuestionImage))->toBeFalse()
        ->and(Storage::disk('public')->exists($a->image_path))->toBeFalse()
        ->and(Storage::disk('public')->exists($b->image_path))->toBeFalse()
        ->and($q->options->pluck('image_path')->map(fn ($p) => $p !== null)->all())->toBe([false, true])
        ->and(storedFiles())->toHaveCount(2); // new question image + option C

    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", imagePayload($this, [
        'remove_image' => '1',
        'options' => [
            ['id' => $a->id, 'text' => 'A', 'is_correct' => '1'],
            ['id' => $c->id, 'text' => 'C', 'is_correct' => '0'],
        ],
    ]))->assertSessionHasNoErrors();

    expect($q->fresh()->image_path)->toBeNull()
        ->and($q->options()->find($c->id)->image_path)->not->toBeNull(); // untouched option keeps its image
});

it('counts a saved option image as content, unless it is being removed', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', imagePayload($this, ['options' => [
        ['text' => '', 'is_correct' => '1', 'image' => UploadedFile::fake()->image('a.png')],
        ['text' => 'B', 'is_correct' => '0'],
    ]]));
    $q = Question::with('options')->latest('id')->first();
    $a = $q->options[0];

    $put = fn (array $optionA) => $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", imagePayload($this, ['options' => [
        ['id' => $a->id, 'text' => '', 'is_correct' => '1', ...$optionA],
        ['id' => $q->options[1]->id, 'text' => 'B', 'is_correct' => '0'],
    ]]));

    $put([])->assertSessionHasNoErrors();
    $put(['remove_image' => '1'])->assertSessionHasErrors(['options.0.text' => 'Option 1 needs text or an image.']);
});

it('keeps old files and drops new ones when a save fails half-way', function () {
    $service = app(QuestionService::class);
    $question = $service->create($this->teacher, [
        ...imagePayload($this), 'image' => UploadedFile::fake()->image('old.png'), 'tag_names' => [],
    ]);
    $old = $question->image_path;

    // A new image is stored first, then an option fails to save (text too long for MySQL).
    expect(fn () => $service->update($question, [
        ...imagePayload($this), 'tag_names' => [],
        'image' => UploadedFile::fake()->image('new.png'),
        'options' => [['text' => str_repeat('x', 70000), 'is_correct' => '1'], ['text' => 'B', 'is_correct' => '0']],
    ]))->toThrow(QueryException::class);

    expect($question->fresh()->image_path)->toBe($old)
        ->and(Storage::disk('public')->exists($old))->toBeTrue()  // not deleted: the transaction rolled back
        ->and(storedFiles())->toBe([$old]);                        // the new file was cleaned up
});

it('keeps a locked question\'s option images, but lets its own image change', function () {
    $test = publishedTest();
    $q = questionsOf($test)->first();
    $q->forceFill(['created_by' => $this->teacher->id])->save(); // created_by is not mass-assignable
    $before = $q->options()->pluck('image_path', 'id')->all();

    $this->actingAs($this->teacher)->put("/teacher/questions/{$q->id}", imagePayload($this, [
        'topic_id' => $q->topic_id,
        'image' => UploadedFile::fake()->image('diagram.png'),
        // crafted: tries to add an image to an option of a published question
        'options' => [['id' => $q->options->first()->id, 'text' => 'x', 'is_correct' => '1', 'image' => UploadedFile::fake()->image('hack.png')]],
    ]))->assertRedirect("/teacher/questions/{$q->id}")->assertSessionHasNoErrors();

    expect($q->fresh()->image_path)->not->toBeNull()
        ->and($q->options()->pluck('image_path', 'id')->all())->toBe($before)
        ->and(storedFiles())->toHaveCount(1);
});

it('keeps image files when a question is soft-deleted (old results still show them)', function () {
    $this->actingAs($this->teacher)->post('/teacher/questions', imagePayload($this, ['image' => UploadedFile::fake()->image('d.png')]));
    $q = Question::latest('id')->first();

    $this->actingAs($this->teacher)->delete("/teacher/questions/{$q->id}")->assertSessionHas('success');

    expect(Storage::disk('public')->exists($q->image_path))->toBeTrue();
});

// ---------- students ----------

it('sends image URLs to the test screen but still never the correct answer', function () {
    Queue::fake();
    $test = publishedTest();
    $q = questionsOf($test)->first();
    $q->forceFill(['image_path' => 'questions/diagram.png'])->save();
    $q->options->first()->forceFill(['image_path' => 'questions/opt.png'])->save();
    $student = User::factory()->student()->create();
    $attempt = app(AttemptService::class)->start($student, $test);

    $page = $this->actingAs($student)->get("/student/attempts/{$attempt->id}")->assertOk();
    $first = $page->viewData('questions')->firstWhere('id', $q->id);

    expect($first['image'])->toEndWith('/storage/questions/diagram.png')
        ->and($first['options'][0]['image'])->toEndWith('/storage/questions/opt.png')
        ->and($first['hasOptionImages'])->toBeTrue()
        ->and($page->getContent())->not->toContain('is_correct');
});

it('shows images on the result page', function () {
    $test = publishedTest();
    $q = questionsOf($test)->first();
    $q->forceFill(['image_path' => 'questions/diagram.png'])->save();
    $student = User::factory()->student()->create();
    $attempts = app(AttemptService::class);
    $attempt = $attempts->start($student, $test);
    $attempts->submit($attempt); // sync queue → evaluated

    $this->actingAs($student)->get("/student/attempts/{$attempt->id}/result")
        ->assertOk()->assertSee('/storage/questions/diagram.png', false);
});
