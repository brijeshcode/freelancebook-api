<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 50, 500);
        $totalPrice = $quantity * $unitPrice;

        return [
            'invoice_id' => Invoice::factory(),
            'service_id' => Service::factory(),
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'service_period_start' => $this->faker->optional()->date(),
            'service_period_end' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'is_recurring' => $this->faker->boolean(30),
            'notes' => $this->faker->optional()->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function recurring(): static
    {
        return $this->state([
            'is_recurring' => true,
            'service_period_start' => now()->startOfMonth(),
            'service_period_end' => now()->endOfMonth(),
        ]);
    }

    public function oneTime(): static
    {
        return $this->state([
            'is_recurring' => false,
            'service_period_start' => null,
            'service_period_end' => null,
        ]);
    }
}