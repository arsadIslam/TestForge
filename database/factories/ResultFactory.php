<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * test_id and user_id are copied from the attempt (they are denormalised columns),
 * so a factory result can never point at a different test/student than its attempt.
 *
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    public function definition(): array
    {
        $correct = fake()->numberBetween(0, 20);
        $wrong = fake()->numberBetween(0, 20 - $correct);

        return [
            'attempt_id' => Attempt::factory()->evaluated(),
            'test_id' => fn (array $attributes) => Attempt::find($attributes['attempt_id'])->test_id,
            'user_id' => fn (array $attributes) => Attempt::find($attributes['attempt_id'])->user_id,
            'score' => $correct,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'skipped_count' => 20 - $correct - $wrong,
            'accuracy' => $correct + $wrong > 0 ? round($correct / ($correct + $wrong) * 100, 2) : 0,
            'time_taken_seconds' => fake()->numberBetween(60, 1800),
            'is_passed' => $correct >= 8,
            'evaluated_at' => now(),
        ];
    }
}
