<?php

namespace Database\Seeders;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Completed (evaluated) attempts with answers and results, so the leaderboard
 * and analytics pages have data from day one.
 *
 * Answers are generated, then scored by ScoringService (one source of truth for the
 * scoring rule), so the leaderboard, subject accuracy and "toughest questions"
 * reports all agree with each other.
 */
class AttemptSeeder extends Seeder
{
    /** Chance that a student skips a question. */
    private const SKIP_RATE = 0.08;

    /** Each difficulty lowers the chance of a correct answer by this much. */
    private const DIFFICULTY_PENALTY = [
        'easy' => 0.0,
        'medium' => 0.12,
        'hard' => 0.25,
    ];

    /** @var array<int, float> user_id => ability (0–1) */
    private array $abilities = [];

    public function __construct(private ScoringService $scoring) {}

    public function run(): void
    {
        $students = User::where('role', UserRole::Student)->orderBy('id')->get();
        $demoStudent = $students->firstWhere('email', 'student1@testforge.test');

        foreach ($students as $student) {
            $this->abilities[$student->id] = fake()->randomFloat(2, 0.50, 0.98);
        }

        // Eager load everything the loop touches — preventLazyLoading() is on locally.
        $tests = Test::with('questions.options')
            ->whereIn('slug', ['mathematics-mock-test-1', 'science-combined-test'])
            ->get()
            ->keyBy('slug');

        // Maths: 40 students, 15 of them try a second time (max_attempts = 2).
        $mathStudents = $this->pickStudents($students, $demoStudent, 40);
        $this->seedAttempts($tests['mathematics-mock-test-1'], $mathStudents, $mathStudents->take(15));

        // Science: 35 students, one attempt each.
        $this->seedAttempts($tests['science-combined-test'], $this->pickStudents($students, $demoStudent, 35));
    }

    /**
     * Random students, always including the demo login so it has results to show.
     *
     * @return Collection<int, User>
     */
    private function pickStudents(Collection $students, User $demoStudent, int $count): Collection
    {
        $fresh = $students->firstWhere('email', 'student2@testforge.test'); // stays untouched for the checkout demo

        return $students->except(array_filter([$demoStudent->getKey(), $fresh?->getKey()]))
            ->random($count - 1)
            ->prepend($demoStudent)
            ->values();
    }

    /**
     * @param  Collection<int, User>  $students
     * @param  Collection<int, User>  $secondAttemptStudents
     */
    private function seedAttempts(Test $test, Collection $students, ?Collection $secondAttemptStudents = null): void
    {
        $secondIds = $secondAttemptStudents?->pluck('id')->all() ?? [];

        // One transaction per test: all-or-nothing, and far fewer disk flushes than autocommit.
        DB::transaction(function () use ($test, $students, $secondIds): void {
            foreach ($students as $student) {
                $ability = $this->abilities[$student->id];

                // First attempt 7–13 days ago.
                $this->createEvaluatedAttempt(
                    $test,
                    $student,
                    attemptNo: 1,
                    startedAt: now()->subDays(fake()->numberBetween(7, 13))->setTime(fake()->numberBetween(8, 21), fake()->numberBetween(0, 59)),
                    ability: $ability,
                );

                // Second attempt 1–6 days ago — students usually improve a little.
                if (in_array($student->id, $secondIds, true)) {
                    $this->createEvaluatedAttempt(
                        $test,
                        $student,
                        attemptNo: 2,
                        startedAt: now()->subDays(fake()->numberBetween(1, 6))->setTime(fake()->numberBetween(8, 21), fake()->numberBetween(0, 59)),
                        ability: min(0.98, $ability + 0.1),
                    );
                }
            }
        });
    }

    /**
     * Seeds what a real student would leave behind — a submitted attempt with answers —
     * and then scores it with ScoringService, the same code the EvaluateAttempt job runs.
     * The seeder itself contains no scoring logic.
     */
    private function createEvaluatedAttempt(Test $test, User $student, int $attemptNo, Carbon $startedAt, float $ability): void
    {
        $durationSeconds = $test->duration_minutes * 60;
        $timeTaken = fake()->numberBetween((int) ($durationSeconds * 0.4), $durationSeconds);
        $submittedAt = $startedAt->copy()->addSeconds($timeTaken);

        $attempt = Attempt::create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'attempt_no' => $attemptNo,
            'status' => AttemptStatus::Submitted,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes($test->duration_minutes),
            'submitted_at' => $submittedAt,
            'ip_address' => fake()->ipv4(),
        ]);

        $optionRows = [];

        foreach ($test->questions as $question) {
            if (fake()->boolean((int) (self::SKIP_RATE * 100))) {
                continue; // unanswered → no attempt_answers row
            }

            $answer = $attempt->answers()->create([
                'question_id' => $question->id,
                'answered_at' => fake()->dateTimeBetween($startedAt, $submittedAt),
            ]);

            foreach ($this->chooseOptions($question, $ability) as $optionId) {
                $optionRows[] = ['attempt_answer_id' => $answer->id, 'question_option_id' => $optionId];
            }
        }

        // One bulk INSERT for all selected options of this attempt.
        DB::table('attempt_answer_options')->insert($optionRows);

        $result = $this->scoring->evaluate($attempt);

        // Back-date the evaluation to just after submission, as the queue would have done.
        $result->forceFill(['evaluated_at' => $submittedAt->copy()->addSeconds(fake()->numberBetween(2, 30))])->save();
    }

    /**
     * Pick the options a student "selects". With probability based on ability and
     * difficulty they pick exactly the correct set; otherwise a believable mistake.
     *
     * @return list<int>
     */
    private function chooseOptions(Question $question, float $ability): array
    {
        $correctIds = $question->options->where('is_correct', true)->pluck('id');
        $wrongIds = $question->options->where('is_correct', false)->pluck('id');

        $penalty = self::DIFFICULTY_PENALTY[$question->difficulty->value];
        $chance = max(0.05, min(0.95, $ability - $penalty));

        if (fake()->randomFloat(2, 0, 1) < $chance) {
            return $correctIds->all();
        }

        if ($question->type === QuestionType::Single) {
            return [$wrongIds->random()];
        }

        // Multi-choice mistakes: either missed one correct option (a partial answer,
        // which still scores 0 because there are no partial marks) or added a wrong one.
        return fake()->boolean()
            ? $correctIds->random($correctIds->count() - 1)->all()
            : $correctIds->push($wrongIds->random())->all();
    }
}
