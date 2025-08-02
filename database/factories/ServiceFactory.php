<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $hasProject = $this->faker->boolean(70); // 70% chance of having a project
        $hasTax = $this->faker->boolean(60); // 60% chance of having tax
        $frequency = $this->faker->randomElement(['one-time', 'weekly', 'monthly', 'quarterly', 'half-yearly', 'yearly']);
        
        return [
            'client_id' => Client::factory(),
            'project_id' => $hasProject ? Project::factory() : null,
            'created_by' => User::factory(),
            'title' => $this->faker->randomElement([
                'Website Development',
                'Logo Design',
                'Server Hosting',
                'Maintenance Service',
                'SEO Optimization',
                'Content Writing',
                'Database Setup',
                'API Integration'
            ]),
            'description' => $this->faker->optional()->paragraph(),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'currency' => $this->faker->randomElement(['INR', 'USD', 'EUR', 'GBP']),
            'has_tax' => $hasTax,
            'tax_name' => $hasTax ? $this->faker->randomElement(['GST', 'VAT', 'Sales Tax']) : null,
            'tax_rate' => $hasTax ? $this->faker->randomElement([0, 5, 10, 18, 20]) : 0,
            'tax_type' => $hasTax ? $this->faker->randomElement(['inclusive', 'exclusive']) : 'exclusive',
            'frequency' => $frequency,
            'start_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'next_billing_date' => $frequency !== 'one-time' ? $this->faker->dateTimeBetween('now', '+3 months') : null,
            'end_date' => $this->faker->optional(20)->dateTimeBetween('+6 months', '+2 years'),
            'status' => $this->faker->randomElement(['draft', 'active', 'paused', 'completed', 'cancelled']),
            'is_active' => $this->faker->boolean(80),
            'billing_count' => $this->faker->numberBetween(0, 10),
            'tags' => $this->faker->optional(50)->randomElements(['hosting', 'design', 'development', 'maintenance', 'seo'], 2),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'one-time',
            'next_billing_date' => null,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'next_billing_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    public function withTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_tax' => true,
            'tax_name' => 'GST',
            'tax_rate' => 18.00,
            'tax_type' => 'exclusive',
        ]);
    }

    public function withoutTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_tax' => false,
            'tax_name' => null,
            'tax_rate' => 0.00,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}