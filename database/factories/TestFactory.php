<?php

namespace Database\Factories;

use App\Enums\TestStatus;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Note: published() only sets the status. A real published test also needs
 * questions attached and total_marks recalculated — the seeder and (later) the
 * test builder handle that.
 *
 * @extends Factory<Test>
 */
class TestFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true)).' Test';

        return [
            'created_by' => User::factory()->teacher(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'description' => fake()->optional()->paragraph(),
            'duration_minutes' => fake()->randomElement([15, 20, 30, 45, 60]),
            'total_marks' => 0,
            'pass_marks' => 0,
            'max_attempts' => 1,
            'shuffle_questions' => false,
            'status' => TestStatus::Draft,
            'starts_at' => null,
            'ends_at' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TestStatus::Published,
            'published_at' => now()->subDays(7),
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addDays(30),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => TestStatus::Archived]);
    }
}
