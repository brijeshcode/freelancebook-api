<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $deadline = $this->faker->dateTimeBetween($startDate, '+3 months');
        
        return [
            'client_id' => Client::factory(),
            'freelancer_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'budget' => $this->faker->randomFloat(2, 1000, 50000),
            'budget_currency' => $this->faker->randomElement(['USD', 'EUR', 'INR']),
            'notes' => $this->faker->optional()->paragraph(),
            'project_details' => $this->faker->optional()->paragraphs(3, true),
            'start_date' => $startDate,
            'end_date' => $this->faker->optional()->dateTimeBetween($startDate, 'now'),
            'deadline' => $deadline,
            'estimated_hours' => $this->faker->randomFloat(2, 10, 500),
            'actual_hours' => $this->faker->randomFloat(2, 0, 600),
            'total_paid' => $this->faker->randomFloat(2, 0, 60000),
            'payment_currency' => $this->faker->randomElement(['USD', 'EUR', 'INR']),
            'status' => $this->faker->randomElement([
                'prospective', 'planned', 'active', 'completed', 'on_hold', 'cancelled'
            ]),
        ];
    }

    public function prospective(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'prospective',
            'start_date' => null,
            'end_date' => null,
            'actual_hours' => 0,
            'total_paid' => 0,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'end_date' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => $this->faker->dateTimeBetween('-6 months', '-1 month'),
            'end_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}