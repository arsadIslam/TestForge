<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Question;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 20 topics × 10 questions = 200 questions, each with 4 options and 1–2 tags.
 */
class QuestionSeeder extends Seeder
{
    private const PER_TOPIC = 10;

    private const INACTIVE_COUNT = 10;

    public function run(): void
    {
        $teacherIds = User::where('role', UserRole::Teacher)->pluck('id');
        $tagIds = Tag::pluck('id');

        foreach (Topic::all() as $topic) {
            $questions = Question::factory()
                ->count(self::PER_TOPIC)
                ->withOptions()
                ->for($topic)
                ->state(fn () => ['created_by' => $teacherIds->random()])
                ->create();

            foreach ($questions as $question) {
                $question->tags()->attach($tagIds->random(fake()->numberBetween(1, 2)));
            }
        }

        // A few inactive questions so the "active" filter has something to hide.
        Question::inRandomOrder()->limit(self::INACTIVE_COUNT)->update(['is_active' => false]);
    }
}
