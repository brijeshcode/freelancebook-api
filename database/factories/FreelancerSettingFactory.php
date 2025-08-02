<?php

namespace Database\Factories;

use App\Models\FreelancerSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FreelancerSettingFactory extends Factory
{
    protected $model = FreelancerSetting::class;

    public function definition(): array
    {
        return [
            'freelancer_id' => User::factory(),
            'base_currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'invoice_due_days' => $this->faker->randomElement([15, 30, 45, 60]),
            'invoice_prefix' => $this->faker->randomElement(['INV', 'BILL', 'INVOICE']),
            'next_invoice_number' => $this->faker->numberBetween(1, 100),
            'invoice_year' => date('Y'),
            'invoice_footer' => $this->faker->optional()->sentence(),
            'invoice_branding' => $this->faker->optional()->randomElements([
                'logo_url' => 'https://example.com/logo.png',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#1F2937'
            ]),
            'default_tax_rate' => $this->faker->randomFloat(2, 0, 25),
            'default_tax_label' => $this->faker->randomElement(['VAT', 'GST', 'Tax']),
            'tax_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{9}'),
            'business_address' => $this->faker->optional()->address(),
            'business_phone' => $this->faker->optional()->phoneNumber(),
            'business_email' => $this->faker->optional()->companyEmail(),
            'business_website' => $this->faker->optional()->url(),
            'notification_preferences' => $this->faker->optional()->randomElements([
                'email_invoice_reminders' => true,
                'email_payment_received' => true,
                'in_app_notifications' => true
            ]),
        ];
    }

    public function withPrefix(string $prefix): static
    {
        return $this->state(['invoice_prefix' => $prefix]);
    }

    public function withCurrency(string $currency): static
    {
        return $this->state(['base_currency' => $currency]);
    }
}