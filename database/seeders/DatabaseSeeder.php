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
        $this->call([
            UserSeeder::class,
            SubjectSeeder::class,
            TagSeeder::class,
            QuestionSeeder::class,
            TestSeeder::class,
            AttemptSeeder::class,
        ]);
    }
}
