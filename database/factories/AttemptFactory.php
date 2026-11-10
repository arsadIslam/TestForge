<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'test_id' => Test::factory()->published(),
            'user_id' => User::factory()->student(),
            'attempt_no' => 1,
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }

    /**
     * An in-progress attempt whose time ran out — what attempts:auto-submit picks up.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes(40),
            'expires_at' => now()->subMinutes(10),
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttemptStatus::Submitted,
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(10),
            'submitted_at' => now(),
        ]);
    }

    public function evaluated(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => AttemptStatus::Evaluated,
        ]);
    }
}
