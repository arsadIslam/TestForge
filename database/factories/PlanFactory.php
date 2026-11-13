<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        $days = fake()->randomElement([30, 90, 365]);

        return [
            'name' => "All Access · {$days} days ".fake()->unique()->numerify('##'),
            'price_paise' => $days * 700,
            'duration_days' => $days,
            'is_active' => true,
        ];
    }
}
