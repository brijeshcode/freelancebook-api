<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 50, 3000);
        $exchangeRate = $this->faker->randomFloat(6, 0.5, 2.0);

        return [
            'transaction_number' => 'PAY-' . $this->faker->unique()->numberBetween(100000, 999999),
            'client_id' => Client::factory(),
            'freelancer_id' => User::factory(),
            'amount' => $amount,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'exchange_rate' => $exchangeRate,
            'amount_base_currency' => $amount * $exchangeRate,
            'payment_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'payment_method' => $this->faker->randomElement([
                'bank_transfer', 'paypal', 'stripe', 'western_union', 'cash', 'check', 'crypto'
            ]),
            'transaction_reference' => $this->faker->optional()->regexify('[A-Z0-9]{10}'),
            'notes' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'verified_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'verified_by' => $this->faker->optional()->randomElement([1, 2, 3]),
            'receipt_attachments' => $this->faker->optional()->randomElements([
                'receipts/payment_001.pdf',
                'receipts/bank_statement.jpg'
            ], $this->faker->numberBetween(0, 2)),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'verified_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'verified_at' => null]);
    }
}