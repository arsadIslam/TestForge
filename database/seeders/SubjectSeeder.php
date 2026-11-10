<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubjectSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const SUBJECTS = [
        'Mathematics' => ['Algebra', 'Geometry', 'Trigonometry', 'Probability'],
        'Physics' => ['Mechanics', 'Thermodynamics', 'Optics', 'Electricity'],
        'Chemistry' => ['Atomic Structure', 'Chemical Bonding', 'Organic Chemistry', 'Acids and Bases'],
        'Biology' => ['Cell Biology', 'Genetics', 'Human Physiology', 'Ecology'],
        'Computer Science' => ['Programming Basics', 'Databases', 'Networking', 'Operating Systems'],
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $subjectName => $topics) {
            $subject = Subject::create([
                'name' => $subjectName,
                'slug' => Str::slug($subjectName),
            ]);

            $subject->topics()->createMany(
                array_map(fn (string $topic) => ['name' => $topic, 'slug' => Str::slug($topic)], $topics)
            );
        }
    }
}
