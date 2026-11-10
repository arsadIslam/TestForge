<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        $difficulty = fake()->randomElement(Difficulty::cases());
        $marks = match ($difficulty) {
            Difficulty::Easy => 1,
            Difficulty::Medium => 2,
            Difficulty::Hard => 3,
        };

        return [
            'topic_id' => Topic::factory(),
            'created_by' => User::factory()->teacher(),
            'type' => fake()->randomElement([QuestionType::Single, QuestionType::Single, QuestionType::Multiple]),
            'body' => rtrim(fake()->sentence(12), '.').'?',
            'explanation' => fake()->optional(0.8)->paragraph(),
            'difficulty' => $difficulty,
            'marks' => $marks,
            // Roughly half the questions use negative marking (25% of the marks).
            'negative_marks' => fake()->boolean() ? $marks * 0.25 : 0,
            'is_active' => true,
        ];
    }

    public function single(): static
    {
        return $this->state(fn (array $attributes) => ['type' => QuestionType::Single]);
    }

    public function multiple(): static
    {
        return $this->state(fn (array $attributes) => ['type' => QuestionType::Multiple]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Create answer options that are valid for the question type:
     * single → exactly one correct option, multiple → two or three correct options.
     *
     * Kept as an explicit state (not configure()) so tests can build their own options.
     */
    public function withOptions(int $count = 4): static
    {
        return $this->afterCreating(function (Question $question) use ($count): void {
            $correctCount = $question->type === QuestionType::Single ? 1 : fake()->numberBetween(2, 3);
            $correctPositions = (array) array_rand(range(1, $count), $correctCount);

            // Option texts only need to be distinct within this question (array keys dedupe them).
            $texts = [];
            while (count($texts) < $count) {
                $texts[fake()->words(fake()->numberBetween(1, 4), true)] = true;
            }

            $options = collect(array_keys($texts))->map(fn (string $text, int $index) => [
                'option_text' => $text,
                'is_correct' => in_array($index, $correctPositions, true),
                'sort_order' => $index + 1,
            ]);

            $question->options()->createMany($options->all());
        });
    }
}
