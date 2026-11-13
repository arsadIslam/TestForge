<?php

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuestionImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->teacher = User::factory()->teacher()->create();
    $subject = Subject::create(['name' => 'Mathematics', 'slug' => 'mathematics']);
    $this->topic = $subject->topics()->create(['name' => 'Algebra', 'slug' => 'algebra']);
});

/**
 * Build a CSV from rows of column => value (missing columns are left empty).
 */
function csvFile(array $rows, bool $bom = true, ?array $columns = null): UploadedFile
{
    $columns ??= QuestionImportService::columns();
    $out = fopen('php://temp', 'r+');
    fputcsv($out, array_map('strtoupper', $columns)); // headers are case-insensitive
    foreach ($rows as $row) {
        fputcsv($out, array_map(fn ($c) => $row[$c] ?? '', $columns));
    }
    rewind($out);

    return UploadedFile::fake()->createWithContent('questions.csv', ($bom ? "\xEF\xBB\xBF" : '').stream_get_contents($out));
}

function row(array $override = []): array
{
    return array_merge([
        'subject' => 'mathematics', 'topic' => 'ALGEBRA', 'difficulty' => 'easy', 'marks' => '2',
        'body' => 'What is 2 + 2?', 'option_1' => '3', 'option_2' => '4', 'option_3' => '5', 'correct' => '2',
    ], $override);
}

/**
 * @param  array<string, string>  $files  name inside the ZIP => contents
 */
function zipFile(array $files): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    foreach ($files as $name => $contents) {
        $zip->addFromString($name, $contents);
    }
    $zip->close();

    return new UploadedFile($path, 'images.zip', 'application/zip', null, true);
}

function png(int $w = 60, int $h = 40): string
{
    return UploadedFile::fake()->image('x.png', $w, $h)->getContent();
}

function upload(object $t, UploadedFile $csv, ?UploadedFile $zip = null)
{
    return $t->actingAs($t->teacher)->post('/teacher/questions/import', array_filter(['csv' => $csv, 'zip' => $zip]));
}

function previewOf(object $t, UploadedFile $csv, ?UploadedFile $zip = null)
{
    $response = upload($t, $csv, $zip)->assertRedirect();

    return $t->actingAs($t->teacher)->get($response->headers->get('Location'))->assertOk();
}

it('downloads a template that imports cleanly as it is', function () {
    $response = $this->actingAs($this->teacher)->get('/teacher/questions/import/template')->assertOk();
    $content = $response->streamedContent();

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($content)->toContain('subject,topic,difficulty');

    $preview = previewOf($this, UploadedFile::fake()->createWithContent('t.csv', $content))->viewData('preview');
    expect($preview->isValid())->toBeTrue()->and($preview->rows)->toHaveCount(3);
});

it('previews, then imports every row with options, tags and a derived type', function () {
    $page = previewOf($this, csvFile([
        row(['tags' => 'Arithmetic|important']),
        row(['body' => 'Which are even?', 'option_4' => '6', 'correct' => '2|4', 'negative_marks' => '0.5']),
    ]))->assertSeeText('2 OK')->assertSee('Import 2 questions');

    expect(Question::count())->toBe(0); // the preview saves nothing

    $this->actingAs($this->teacher)->post('/teacher/questions/import/'.$page->viewData('import'))
        ->assertRedirect('/teacher/questions')->assertSessionHas('success', 'Imported 2 questions.');

    $single = Question::with('options', 'tags')->where('body', 'What is 2 + 2?')->first();
    $multi = Question::with('options')->where('body', 'Which are even?')->first();

    expect($single->type)->toBe(QuestionType::Single)
        ->and($single->created_by)->toBe($this->teacher->id)
        ->and($single->topic_id)->toBe($this->topic->id)
        ->and($single->options->pluck('option_text')->all())->toBe(['3', '4', '5'])
        ->and($single->options->pluck('is_correct')->all())->toBe([false, true, false])
        ->and($single->tags->pluck('name')->sort()->values()->all())->toBe(['Arithmetic', 'important'])
        ->and($multi->type)->toBe(QuestionType::Multiple)
        ->and($multi->negative_marks)->toBe('0.50')
        ->and(Storage::disk('local')->allFiles('imports'))->toBeEmpty(); // upload cleaned up
});

it('reports row-numbered errors and imports nothing while any row is wrong', function () {
    $page = previewOf($this, csvFile([
        row(),                                                         // row 2: fine
        row(['topic' => 'Calculus']),                                  // row 3
        row(['correct' => '4']),                                       // row 4: option 4 is empty
        row(['correct' => '']),                                        // row 5
        row(['body' => '']),                                           // row 6
        row(['difficulty' => 'extreme', 'negative_marks' => '5']),     // row 7
        row(['option_2' => '3']),                                      // row 8: duplicate text
    ]));

    $preview = $page->viewData('preview');
    $errors = collect($preview->rows)->mapWithKeys(fn ($r) => [$r['number'] => implode(' | ', $r['errors'])]);

    expect($preview->isValid())->toBeFalse()
        ->and($errors[2])->toBe('')
        ->and($errors[3])->toContain('Unknown topic "Calculus"')
        ->and($errors[4])->toContain('option 4, which is empty')
        ->and($errors[5])->toContain('Mark at least one option as correct.')
        ->and($errors[6])->toContain('body field is required')
        ->and($errors[7])->toContain('selected difficulty is invalid')->toContain('Negative marks cannot be more')
        ->and($errors[8])->toContain('Options 1 and 2 have the same text.');

    $page->assertDontSee('Import 7');
    $this->actingAs($this->teacher)->post('/teacher/questions/import/'.$page->viewData('import'))
        ->assertSessionHas('error');
    expect(Question::count())->toBe(0);
});

it('rejects files with missing columns, no rows or too many rows', function () {
    expect(previewOf($this, csvFile([row()], columns: ['subject', 'topic', 'body']))->viewData('preview')->fileErrors[0])
        ->toContain('Missing column(s): option_1, option_2, correct');

    expect(previewOf($this, csvFile([]))->viewData('preview')->fileErrors[0])->toContain('no questions');

    $tooMany = array_fill(0, QuestionImportService::MAX_ROWS + 1, row());
    expect(previewOf($this, csvFile($tooMany))->viewData('preview')->fileErrors[0])->toContain('at most 500');
});

it('imports images from the ZIP under random names', function () {
    $page = previewOf($this, csvFile([
        row(['image' => 'diagram.PNG', 'option_1' => '', 'option_1_image' => 'shapes/q1_correct.png', 'correct' => '1']),
    ]), zipFile(['diagram.png' => png(), 'shapes/q1_correct.png' => png(30, 30)]));

    expect($page->viewData('preview')->isValid())->toBeTrue();

    $this->actingAs($this->teacher)->post('/teacher/questions/import/'.$page->viewData('import'))->assertSessionHas('success');

    $q = Question::with('options')->first();
    expect($q->image_path)->toStartWith('questions/')
        ->and($q->options[0]->image_path)->toStartWith('questions/')
        ->and($q->options[0]->option_text)->toBeNull()
        ->and(implode(' ', Storage::disk('public')->allFiles('questions')))->not->toContain('correct');
});

it('checks every image before importing anything', function () {
    $errors = fn (array $row, ?UploadedFile $zip) => implode(' | ', previewOf($this, csvFile([row($row)]), $zip)->viewData('preview')->rows[0]['errors']);

    expect($errors(['image' => 'a.png'], null))->toContain('no ZIP file was uploaded')
        ->and($errors(['image' => 'missing.png'], zipFile(['a.png' => png()])))->toContain('"missing.png" is not in the ZIP')
        ->and($errors(['image' => 'fake.png'], zipFile(['fake.png' => '<?php echo "hi";'])))->toContain('is not a JPG, PNG or WebP image')
        ->and($errors(['image' => 'huge.png'], zipFile(['huge.png' => png(4100, 10)])))->toContain('4000 × 4000');
});

it('re-validates on confirm instead of trusting the preview', function () {
    $page = previewOf($this, csvFile([row()]));
    expect($page->viewData('preview')->isValid())->toBeTrue();

    $this->topic->delete(); // the topic disappears between preview and confirm

    $this->actingAs($this->teacher)->post('/teacher/questions/import/'.$page->viewData('import'))->assertSessionHas('error');
    expect(Question::count())->toBe(0);
});

it('keeps each teacher\'s uploads private', function () {
    $uuid = $this->actingAs($this->teacher)->get(
        upload($this, csvFile([row()]))->headers->get('Location')
    )->viewData('import');
    $other = User::factory()->teacher()->create();

    $this->actingAs($other)->get("/teacher/questions/import/{$uuid}")->assertNotFound();
    $this->actingAs($other)->post("/teacher/questions/import/{$uuid}")->assertNotFound();
    $this->actingAs($this->teacher)->get('/teacher/questions/import/..%2F..%2Fetc')->assertNotFound();
    $this->actingAs(User::factory()->student()->create())->get('/teacher/questions/import')->assertForbidden();
});

it('can cancel an upload, and prunes abandoned ones', function () {
    $uuid = $this->actingAs($this->teacher)->get(upload($this, csvFile([row()]))->headers->get('Location'))->viewData('import');

    $this->actingAs($this->teacher)->delete("/teacher/questions/import/{$uuid}")->assertRedirect('/teacher/questions/import');
    expect(Storage::disk('local')->allFiles('imports'))->toBeEmpty();

    $old = $this->actingAs($this->teacher)->get(upload($this, csvFile([row()]))->headers->get('Location'))->viewData('import');
    upload($this, csvFile([row()])); // a fresh one survives
    // prune compares the file's real modification time, so age the old upload's file directly
    touch(Storage::disk('local')->path("imports/{$this->teacher->id}/{$old}/questions.csv"), now()->subHours(25)->getTimestamp());

    $this->artisan('imports:prune')->expectsOutput('Removed 1 abandoned import(s).');
    expect(Storage::disk('local')->directories('imports/'.$this->teacher->id))->toHaveCount(1);
});

it('rejects a non-CSV upload', function () {
    upload($this, UploadedFile::fake()->image('photo.png'))->assertSessionHasErrors('csv');
});
