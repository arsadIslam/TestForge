<?php

namespace Database\Seeders;

use App\Enums\TestStatus;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
        $test = $teacher->tests()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => 'Practice test covering '.implode(', ', array_map('ucwords', str_replace('-', ' ', $subjects))).'.',
            'duration_minutes' => $durationMinutes,
            'pass_marks' => 0, // set below, once total_marks is known
            'max_attempts' => $maxAttempts,
            'shuffle_questions' => $shuffle,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $subjectIds = Subject::whereIn('slug', $subjects)->pluck('id');

        $questions = Question::active()
            ->whereHas('topic', fn ($q) => $q->whereIn('subject_id', $subjectIds))
            ->inRandomOrder()
            ->limit($questionCount)
            ->get();

        // Copy marks/negative_marks into the pivot so later edits to a question never change this test.
        $test->questions()->attach(
            $questions->values()->mapWithKeys(fn (Question $question, int $index) => [
                $question->id => [
                    'sort_order' => $index + 1,
                    'marks' => $question->marks,
                    'negative_marks' => $question->negative_marks,
                ],
            ])->all()
        );

        // total_marks always comes from the pivot rows — the single source of truth.
        $totalMarks = (float) $test->questions()->sum('test_question.marks');

        $test->forceFill([
            'total_marks' => $totalMarks,
            'pass_marks' => round($totalMarks * 0.4, 2),
            'status' => TestStatus::Published,
            'published_at' => $startsAt,
        ])->save();

        return $test;
    }
}
