<?php

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use Database\Seeders\MathQuestionSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;

/*
| LaTeX is rendered in the browser by KaTeX; the server's job is to put the text on the page
| ESCAPED, unchanged (backslashes intact), inside an element marked data-math.
*/

it('escapes HTML and marks the text for maths rendering', function () {
    $html = Blade::render('<x-rich-text :text="$t" />', ['t' => '<b onclick="x()">bold</b> \( x^2 \)']);

    expect($html)->toContain('data-math')
        ->toContain('&lt;b onclick=&quot;x()&quot;&gt;bold&lt;/b&gt;')
        ->toContain('\( x^2 \)')
        ->not->toContain('<b onclick');
});

it('renders nothing for empty text', function () {
    expect(trim(Blade::render('<x-rich-text :text="$t" />', ['t' => null])))->toBe('');
});

it('shows question, options and explanation as maths on the teacher page', function () {
    $teacher = User::factory()->teacher()->create();
    $q = Question::factory()->single()->for($teacher, 'creator')->create([
        'body' => 'Simplify \( \frac{2}{4} \)', 'explanation' => 'Divide by 2: $$ \frac{1}{2} $$',
    ]);
    $q->options()->create(['option_text' => '\( \frac{1}{2} \)', 'is_correct' => true, 'sort_order' => 1]);
    $q->options()->create(['option_text' => '\( 2 \)', 'is_correct' => false, 'sort_order' => 2]);

    $this->actingAs($teacher)->get("/teacher/questions/{$q->id}")->assertOk()
        ->assertSee('data-math', false)
        ->assertSee('Simplify \( \frac{2}{4} \)', false)
        ->assertSee('$$ \frac{1}{2} $$', false)
        ->assertSee('\( \frac{1}{2} \)', false);
});

it('sends LaTeX to the test screen unchanged', function () {
    Queue::fake();
    $test = publishedTest();
    $q = questionsOf($test)->first();
    $q->update(['body' => 'If \( a^2 = 9 \) and costs $5, find \( a \)']);
    $student = User::factory()->student()->create();
    $attempt = app(AttemptService::class)->start($student, $test);

    $body = $this->actingAs($student)->get("/student/attempts/{$attempt->id}")
        ->viewData('questions')->firstWhere('id', $q->id)['body'];

    expect($body)->toBe('If \( a^2 = 9 \) and costs $5, find \( a \)');
});

it('seeds LaTeX questions with types derived from their answers', function () {
    $this->seed([SubjectSeeder::class, TagSeeder::class]);
    User::factory()->teacher()->create(['email' => 'teacher1@testforge.test']);

    $this->seed(MathQuestionSeeder::class);

    expect(Question::count())->toBe(7)
        ->and(Question::where('body', 'like', '%roots of%')->value('type'))->toBe(QuestionType::Multiple)
        ->and(Question::where('body', 'like', 'Solve for%')->value('type'))->toBe(QuestionType::Single)
        ->and(Question::where('body', 'like', '%HCl%')->value('body'))->toContain('\ce{HCl}');
});
