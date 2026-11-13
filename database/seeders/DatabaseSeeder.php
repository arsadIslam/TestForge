<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Order matters: each seeder reads rows created by the ones before it.
     */
    public function run(): void
    {
        // Diagram questions write real image files; the test suite seeds often, so it skips them
        // (DiagramQuestionSeeder has its own test with a fake disk).
        $diagrams = app()->runningUnitTests() ? [] : [DiagramQuestionSeeder::class];

        $this->call([
            UserSeeder::class,
            SubjectSeeder::class,
            TagSeeder::class,
            QuestionSeeder::class,
            MathQuestionSeeder::class,
            ...$diagrams,
            TestSeeder::class,
            AttemptSeeder::class,
            SeriesSeeder::class,
        ]);
    }
}
