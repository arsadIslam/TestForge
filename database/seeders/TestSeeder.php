<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Services\TestService;
use Illuminate\Database\Seeder;

/**
 * Three published tests. AttemptSeeder gives the first two a history of attempts;
 * the third is left untouched so a student can take it live in the demo.
 */
class TestSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = User::where('email', 'like', 'teacher%@testforge.test')->orderBy('id')->get();

        $this->createTest(
            teacher: $teachers[0],
            title: 'Mathematics Mock Test 1',
            subjects: ['mathematics'],
            questionCount: 20,
            durationMinutes: 30,
            maxAttempts: 2,
            startsAt: now()->subDays(14),
            endsAt: now()->addDays(30),
        );

        $this->createTest(
            teacher: $teachers[1],
            title: 'Science Combined Test',
            subjects: ['physics', 'chemistry', 'biology'],
            questionCount: 24,
            durationMinutes: 45,
            maxAttempts: 1,
            startsAt: now()->subDays(14),
            endsAt: now()->addDays(30),
        );

        $this->createTest(
            teacher: $teachers[2],
            title: 'Computer Science Quiz',
            subjects: ['computer-science'],
            questionCount: 15,
            durationMinutes: 20,
            maxAttempts: 1,
            startsAt: now()->subDay(),
            endsAt: null,
            shuffle: true,
        );
    }

    /**
     * Goes through TestService — the same code path as the teacher UI — so seeded
     * tests obey the same rules (copied pivot marks, total_marks from the pivot, publish checks).
     *
     * @param  list<string>  $subjects  subject slugs to draw questions from
     */
    private function createTest(
        User $teacher,
        string $title,
        array $subjects,
        int $questionCount,
        int $durationMinutes,
        int $maxAttempts,
        \DateTimeInterface $startsAt,
        ?\DateTimeInterface $endsAt,
        bool $shuffle = false,
    ): Test {
        $service = app(TestService::class);

        $test = $service->create($teacher, [
            'title' => $title,
            'description' => 'Practice test covering '.implode(', ', array_map('ucwords', str_replace('-', ' ', $subjects))).'.',
            'duration_minutes' => $durationMinutes,
            'pass_marks' => 0, // set below, once total_marks is known
            'max_attempts' => $maxAttempts,
            'shuffle_questions' => $shuffle,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $subjectIds = Subject::whereIn('slug', $subjects)->pluck('id');

        $questionIds = Question::active()
            ->whereHas('topic', fn ($q) => $q->whereIn('subject_id', $subjectIds))
            ->inRandomOrder()
            ->limit($questionCount)
            ->pluck('id')
            ->all();

        $service->attachQuestions($test, $questionIds);

        $test->refresh();
        $service->update($test, ['pass_marks' => round((float) $test->total_marks * 0.4, 2)]);
        $service->publish($test);

        // Back-date publication so it comes before the seeded attempts (7–13 days ago).
        $test->refresh()->forceFill(['published_at' => $startsAt])->save();

        return $test;
    }
}
