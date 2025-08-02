<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $taxRate = $this->faker->randomFloat(2, 0, 25);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        return [
            'invoice_number' => 'INV-' . date('Y') . '-' . $this->faker->unique()->numberBetween(1, 999),
            'client_id' => Client::factory(),
            'project_id' => Project::factory(),
            'freelancer_id' => User::factory(),
            'invoice_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'notes' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['draft', 'sent', 'paid', 'overdue']),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'exchange_rate' => $this->faker->randomFloat(6, 0.5, 2.0),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'total_amount_base_currency' => $total,
            'tax_rate' => $taxRate,
            'tax_label' => $this->faker->randomElement(['VAT', 'GST', 'Tax']),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'sent_at' => null]);
    }

    public function sent(): static
    {
        return $this->state(['status' => 'sent', 'sent_at' => now()]);
    }

    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }
}