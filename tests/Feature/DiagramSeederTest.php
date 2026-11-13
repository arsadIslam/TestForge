<?php

use App\Models\Question;
use App\Models\User;
use Database\Seeders\DiagramQuestionSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds diagram questions with GD-drawn images under random names', function () {
    Storage::fake('public');
    $this->seed([SubjectSeeder::class, TagSeeder::class]);
    User::factory()->teacher()->create(['email' => 'teacher2@testforge.test']);

    $this->seed(DiagramQuestionSeeder::class);

    $shapes = Question::with('options')->where('body', 'like', '%three sides%')->sole();
    $chart = Question::where('body', 'like', '%sales%')->sole();

    expect($shapes->options)->toHaveCount(4)
        ->and($shapes->options->every(fn ($o) => $o->image_path !== null && $o->option_text === null))->toBeTrue()
        ->and($shapes->options->where('is_correct', true))->toHaveCount(1)
        ->and($chart->image_path)->not->toBeNull()
        ->and(Storage::disk('public')->allFiles('questions'))->toHaveCount(5);

    // real PNGs, readable by GD
    foreach (Storage::disk('public')->allFiles('questions') as $file) {
        expect(getimagesizefromstring(Storage::disk('public')->get($file))['mime'])->toBe('image/png');
    }
});
