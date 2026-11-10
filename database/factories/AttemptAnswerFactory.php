<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttemptAnswer>
 */
class AttemptAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attempt_id' => Attempt::factory(),
            'question_id' => Question::factory()->withOptions(),
            'is_correct' => null, // not scored yet
            'marks_awarded' => 0,
            'answered_at' => now(),
        ];
    }
}
