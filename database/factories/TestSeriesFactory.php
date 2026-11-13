<?php

namespace Database\Factories;

use App\Models\TestSeries;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TestSeries>
 */
class TestSeriesFactory extends Factory
{
    public function definition(): array
    {
        $name = strtoupper(fake()->unique()->lexify('???')).' Exam Series';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price_paise' => 29900,
            'validity_days' => 365,
            'is_active' => true,
        ];
    }

    public function lifetime(): static
    {
        return $this->state(fn () => ['validity_days' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
