<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = User::where('role', 'freelancer')->first();
        $clients    = Client::all()->keyBy('client_code');

        $usd = Currency::where('code', 'USD')->first();
        $gbp = Currency::where('code', 'GBP')->first();
        $inr = Currency::where('code', 'INR')->first();
        $eur = Currency::where('code', 'EUR')->first();

        $projects = [
            // David (CLI001)
            [
                'client_code'     => 'CLI001',
                'name'            => 'E-Commerce Platform Redesign',
                'currency'        => $usd,
                'budget'          => 12000.00,
                'notes'           => 'Full redesign of the storefront and checkout flow.',
                'project_details' => 'Includes UX audit, wireframes, UI design, front-end implementation.',
                'start_date'      => '2025-02-01',
                'end_date'        => '2025-05-31',
                'deadline'        => '2025-05-15',
                'estimated_hours' => 320.00,
                'actual_hours'    => 290.00,
                'total_paid'      => 8000.00,
                'status'          => 'active',
            ],
            [
                'client_code'     => 'CLI001',
                'name'            => 'Mobile App MVP',
                'currency'        => $usd,
                'budget'          => 8500.00,
                'notes'           => 'iOS and Android MVP for logistics tracking.',
                'project_details' => 'React Native app with real-time GPS tracking and push notifications.',
                'start_date'      => '2025-06-01',
                'end_date'        => null,
                'deadline'        => '2025-09-30',
                'estimated_hours' => 240.00,
                'actual_hours'    => 60.00,
                'total_paid'      => 2000.00,
                'status'          => 'planned',
            ],

            // Mark (CLI002)
            [
                'client_code'     => 'CLI002',
                'name'            => 'Brand Identity Package',
                'currency'        => $gbp,
                'budget'          => 3500.00,
                'notes'           => 'Logo, typography, colour palette, brand guidelines.',
                'project_details' => null,
                'start_date'      => '2025-01-15',
                'end_date'        => '2025-03-15',
                'deadline'        => '2025-03-10',
                'estimated_hours' => 80.00,
                'actual_hours'    => 88.00,
                'total_paid'      => 3500.00,
                'status'          => 'completed',
            ],
            [
                'client_code'     => 'CLI002',
                'name'            => 'Campaign Landing Pages',
                'currency'        => $gbp,
                'budget'          => 2200.00,
                'notes'           => '4 landing pages for Q3 ad campaign.',
                'project_details' => 'High-conversion pages with A/B testing variants.',
                'start_date'      => '2025-07-01',
                'end_date'        => null,
                'deadline'        => '2025-07-25',
                'estimated_hours' => 60.00,
                'actual_hours'    => 0.00,
                'total_paid'      => 0.00,
                'status'          => 'prospective',
            ],

            // Nick (CLI003)
            [
                'client_code'     => 'CLI003',
                'name'            => 'Fitness Tracker App',
                'currency'        => $inr,
                'budget'          => 180000.00,
                'notes'           => 'Cross-platform fitness app with workout plans and nutrition tracking.',
                'project_details' => 'Flutter app, Firebase backend, Google Fit integration.',
                'start_date'      => '2025-03-01',
                'end_date'        => null,
                'deadline'        => '2025-08-31',
                'estimated_hours' => 400.00,
                'actual_hours'    => 210.00,
                'total_paid'      => 90000.00,
                'status'          => 'active',
            ],

            // Pixel & Ink Studio (CLI004)
            [
                'client_code'     => 'CLI004',
                'name'            => 'Annual Report Design 2024',
                'currency'        => $eur,
                'budget'          => 4800.00,
                'notes'           => 'Print and digital version of the annual report.',
                'project_details' => 'InDesign layout, data visualisation, custom illustrations.',
                'start_date'      => '2025-01-10',
                'end_date'        => '2025-02-28',
                'deadline'        => '2025-02-20',
                'estimated_hours' => 120.00,
                'actual_hours'    => 115.00,
                'total_paid'      => 4800.00,
                'status'          => 'completed',
            ],
            [
                'client_code'     => 'CLI004',
                'name'            => 'Social Media Content Pack',
                'currency'        => $eur,
                'budget'          => 1800.00,
                'notes'           => 'Monthly retainer: 30 posts/month across Instagram, LinkedIn.',
                'project_details' => null,
                'start_date'      => '2025-04-01',
                'end_date'        => null,
                'deadline'        => null,
                'estimated_hours' => 40.00,
                'actual_hours'    => 40.00,
                'total_paid'      => 1800.00,
                'status'          => 'active',
            ],
        ];

        foreach ($projects as $data) {
            $client   = $clients[$data['client_code']];
            $currency = $data['currency'];
            $rate     = CurrencyRate::where('currency_id', $currency->id)->where('is_active', true)->first();

            $exchangeRate    = (float) $rate->rate;
            $calculationType = $rate->calculation_type;

            $budgetBase = $calculationType === 'divide'
                ? round($data['budget'] / $exchangeRate, 2)
                : round($data['budget'] * $exchangeRate, 2);

            $totalPaidBase = $calculationType === 'divide'
                ? round($data['total_paid'] / $exchangeRate, 2)
                : round($data['total_paid'] * $exchangeRate, 2);

            Project::create([
                'client_id'                => $client->id,
                'freelancer_id'            => $freelancer->id,
                'name'                     => $data['name'],
                'currency_id'              => $currency->id,
                'exchange_rate'            => $exchangeRate,
                'calculation_type'         => $calculationType,
                'budget'                   => $data['budget'],
                'budget_base_currency'     => $budgetBase,
                'notes'                    => $data['notes'],
                'project_details'          => $data['project_details'],
                'start_date'               => $data['start_date'],
                'end_date'                 => $data['end_date'],
                'deadline'                 => $data['deadline'],
                'estimated_hours'          => $data['estimated_hours'],
                'actual_hours'             => $data['actual_hours'],
                'total_paid'               => $data['total_paid'],
                'total_paid_base_currency' => $totalPaidBase,
                'status'                   => $data['status'],
            ]);
        }
    }
}
