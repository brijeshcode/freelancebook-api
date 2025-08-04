<?php

namespace Database\Seeders;

use App\Models\FreelancerSetting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'brijesh@example.com',
            'password' => 'testtest',
            'role' => 'freelancer'
        ]);

        $freelancers = User::where('id', 1)->get();
        foreach ($freelancers as $freelancer) {
            FreelancerSetting::create([
                'freelancer_id' => $freelancer->id,
                'base_currency' => 'USD',
                'invoice_due_days' => 30,
                'invoice_prefix' => 'INV',
                'next_invoice_number' => 1,
                'invoice_year' => now()->year,
                'invoice_footer' => 'Thank you for your business.',
                'invoice_branding' => json_encode([
                    'logo_url' => 'https://example.com/logo.png',
                    'primary_color' => '#1d4ed8',
                    'secondary_color' => '#64748b',
                ]),
                'default_tax_rate' => 18.00,
                'default_tax_label' => 'GST',
                'tax_number' => 'GSTIN123456',
                'business_address' => '123 Freelancer St, Remote City, World',
                'business_phone' => '+1-555-123-4567',
                'business_email' => 'freelancer@example.com',
                'business_website' => 'https://freelancer.example.com',
                'notification_preferences' => json_encode([
                    'email_notifications' => true,
                    'in_app_notifications' => true,
                    'sms_notifications' => false,
                ]),
            ]);
        }
    }
}
