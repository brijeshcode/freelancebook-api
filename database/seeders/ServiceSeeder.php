<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    private function resolveStatus(bool $active, bool $archived): string
    {
        if ($active && !$archived) return 'active';
        if ($active && $archived)  return 'paused';
        if (!$active && $archived) return 'cancelled';
        return 'paused'; // active:false, archived:false
    }

    private function nextBillingDate(string $frequency, bool $isActive, ?int $chargeMonth = null): ?string
    {
        if (!$isActive) return null;

        return match ($frequency) {
            'monthly'     => '2026-03-01',
            'yearly'      => $chargeMonth ? sprintf('2026-%02d-01', $chargeMonth) : '2026-07-01',
            'quarterly'   => '2026-06-01',
            'half-yearly' => '2026-07-01',
            default       => null,
        };
    }

    public function run(): void
    {
        $freelancer = User::where('role', 'freelancer')->first();
        $clients    = Client::all()->keyBy('client_code');

        $david = $clients['CLI001'];
        $mark  = $clients['CLI002'];
        $nick  = $clients['CLI003'];

        $usd     = Currency::where('code', 'USD')->first();
        $usdRate = CurrencyRate::where('currency_id', $usd->id)->where('is_active', true)->first();

        $exchangeRate    = (float) $usdRate->rate;
        $calculationType = $usdRate->calculation_type;

        $create = function (array $d) use ($freelancer, $usd, $exchangeRate, $calculationType) {
            $amount    = (float) $d['amount'];
            $amountBase = $calculationType === 'divide'
                ? round($amount / $exchangeRate, 2)
                : round($amount * $exchangeRate, 2);

            Service::create([
                'client_id'            => $d['client_id'],
                'project_id'           => null,
                'created_by'           => $freelancer->id,
                'title'                => $d['title'],
                'description'          => $d['description'] ?? null,
                'amount'               => $amount,
                'currency_id'          => $usd->id,
                'exchange_rate'        => $exchangeRate,
                'calculation_type'     => $calculationType,
                'amount_base_currency' => $amountBase,
                'has_tax'              => false,
                'tax_name'             => null,
                'tax_rate'             => 0.00,
                'tax_type'             => 'exclusive',
                'frequency'            => $d['frequency'],
                'start_date'           => $d['start_date'] ?? '2022-01-01',
                'next_billing_date'    => $d['next_billing_date'] ?? null,
                'end_date'             => null,
                'status'               => $d['status'],
                'is_active'            => $d['is_active'],
                'last_billed_at'       => null,
                'billing_count'        => $d['billing_count'] ?? 0,
                'tags'                 => $d['tags'] ?? null,
                'notes'                => $d['notes'] ?? null,
                'metadata'             => $d['metadata'] ?? null,
            ]);
        };

        // =====================================================================
        // SYSTEMS — David (CLI001), monthly $120 maintenance fee each
        // =====================================================================
        $systems = [
            ['title' => 'Manhattan Club',          'link' => 'https://club2022.greattechsolutions.in',          'active' => true,  'archived' => false],
            ['title' => 'Scarabay2025 Accounts',   'link' => 'https://scarabay2025.greattechsolutions.in',      'active' => true,  'archived' => false, 'billing_count' => 12],
            ['title' => 'Scarabay Accounts',        'link' => 'https://scarabay.greattechsolutions.in',          'active' => true,  'archived' => true,  'billing_count' => 12],
            ['title' => 'Casajose-Marrakech',       'link' => 'https://casajose-marrakech.greattechsolutions.in','active' => true,  'archived' => false],
            ['title' => 'Leopard-Marrakech',        'link' => 'https://leopard-marrakech.greattechsolutions.in', 'active' => true,  'archived' => false],
            ['title' => 'Mandaloun-Marrakech',      'link' => 'https://mandaloun-marrakech.greattechsolutions.in','active' => true,  'archived' => false],
            ['title' => 'Fastshop',                 'link' => 'https://fastshop.greattechsolutions.in',          'active' => true,  'archived' => false],
            ['title' => 'Laislabonita',             'link' => 'https://laislabonita.greattechsolutions.in',      'active' => true,  'archived' => false],
            ['title' => 'AlcoholShop',              'link' => 'https://alcoholShop.greattechsolutions.in',       'active' => false, 'archived' => true],
            ['title' => 'Casajose',                 'link' => 'https://casajose.greattechsolutions.in',          'active' => true,  'archived' => false],
            ['title' => 'Luma',                     'link' => 'https://luma.greattechsolutions.in',              'active' => false, 'archived' => true,  'start_date' => '2022-11-01'],
            ['title' => 'Sidi Rahal',               'link' => 'https://sidirahal.greattechsolutions.in',         'active' => true,  'archived' => false],
            ['title' => 'Mandaloun Accounts',       'link' => 'https://mandaloun.greattechsolutions.in',         'active' => false, 'archived' => true],
            ['title' => 'Mandaloun Casa Accounts',  'link' => 'https://mandalouncasa.greattechsolutions.in',     'active' => true,  'archived' => false, 'start_date' => '2022-09-01', 'billing_count' => 26],
            ['title' => 'Hadswalem Accounts',       'link' => 'https://hadswalem.greattechsolutions.in',         'active' => true,  'archived' => false, 'start_date' => '2022-09-01'],
            ['title' => 'Casa Jose Rabat',          'link' => 'https://casajoserabat.greattechsolutions.in',     'active' => false, 'archived' => true],
            ['title' => 'Darwa',                    'link' => 'https://darwa.greattechsolutions.in',             'active' => true,  'archived' => false],
            ['title' => 'Leopard',                  'link' => 'https://leopard.greattechsolutions.in',           'active' => true,  'archived' => false, 'start_date' => '2024-01-24', 'billing_count' => 28],
            ['title' => 'Leopard 2025',             'link' => 'https://leopard2025.greattechsolutions.in',       'active' => true,  'archived' => false, 'start_date' => '2024-01-24', 'billing_count' => 28],
            ['title' => 'Luigi (2024)',             'link' => 'https://luigi.greattechsolutions.in',             'active' => true,  'archived' => true,  'start_date' => '2024-12-20'],
            ['title' => 'Luigi 2025',               'link' => 'https://luigi2025.greattechsolutions.in',         'active' => true,  'archived' => false, 'start_date' => '2025-12-20'],
            ['title' => 'IVY Supply',               'link' => 'https://ivysupply.greattechsolutions.in',         'active' => true,  'archived' => false, 'start_date' => '2025-11-22', 'desc' => 'Maintenance Fee'],
            ['title' => 'Testing',                  'link' => 'https://testing.greattechsolutions.in',           'active' => false, 'archived' => false, 'amount' => 0, 'desc' => 'Test system server charge till we are testing'],
        ];

        foreach ($systems as $s) {
            $isActive = $s['active'] && !$s['archived'];
            $status   = $this->resolveStatus($s['active'], $s['archived']);

            $create([
                'client_id'         => $david->id,
                'title'             => $s['title'],
                'description'       => $s['desc'] ?? 'Maintenance Fee',
                'amount'            => $s['amount'] ?? 120,
                'frequency'         => 'monthly',
                'start_date'        => $s['start_date'] ?? '2022-01-01',
                'next_billing_date' => $isActive ? '2026-03-01' : null,
                'status'            => $status,
                'is_active'         => $isActive,
                'billing_count'     => $s['billing_count'] ?? 0,
                'tags'              => ['hosting', 'maintenance'],
                'metadata'          => ['link' => $s['link']],
            ]);
        }

        // =====================================================================
        // FIXED RECURRING SERVICES — various clients and frequencies
        // (skipping template entries with no clientId)
        // =====================================================================
        $fixedServices = [
            [
                'client_id'    => $david->id,
                'title'        => 'Luigi Darbouazza Mobile App',
                'description'  => 'Mobile App Maintenance Fee',
                'amount'       => 120,
                'frequency'    => 'monthly',
                'start_date'   => '2025-12-01',
                'charge_month' => null,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['mobile', 'maintenance', 'fixed'],
                'link'         => 'https://luigidarbouazza.ma',
            ],
            [
                'client_id'    => $david->id,
                'title'        => 'Greattechsolutions.in Annual Server Charges',
                'description'  => 'Yearly server fee',
                'amount'       => 240,
                'frequency'    => 'yearly',
                'start_date'   => '2022-07-01',
                'charge_month' => 7,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['server', 'hosting', 'fixed'],
                'link'         => 'https://greattechsolutions.in',
            ],
            [
                'client_id'    => $david->id,
                'title'        => 'Greattechsolutions.in Annual Domain Charges',
                'description'  => 'Yearly domain fee',
                'amount'       => 22,
                'frequency'    => 'yearly',
                'start_date'   => '2022-07-01',
                'charge_month' => 7,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['domain'],
                'link'         => 'https://greattechsolutions.in',
            ],
            [
                'client_id'    => $david->id,
                'title'        => 'luigidarbouazza.ma Annual Server Charges',
                'description'  => 'Yearly server fee',
                'amount'       => 240,
                'frequency'    => 'yearly',
                'start_date'   => '2022-09-01',
                'charge_month' => 9,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['server', 'hosting', 'fixed'],
                'link'         => 'https://luigidarbouazza.ma',
            ],
            [
                'client_id'    => $nick->id,
                'title'        => 'globalzsolutions.com Annual Server Charges',
                'description'  => 'Yearly server fee (Nick)',
                'amount'       => 240,
                'frequency'    => 'yearly',
                'start_date'   => '2025-10-01',
                'charge_month' => 10,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['server', 'hosting', 'fixed'],
                'link'         => 'https://globalzsolutions.com',
            ],
            [
                'client_id'    => $nick->id,
                'title'        => 'globalzsolutions.com Annual Domain Charges',
                'description'  => 'Yearly domain fee (Nick)',
                'amount'       => 22,
                'frequency'    => 'yearly',
                'start_date'   => '2025-10-01',
                'charge_month' => 10,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['domain', 'fixed'],
                'link'         => 'https://globalzsolutions.com',
            ],
            [
                'client_id'    => $nick->id,
                'title'        => 'Live System Maintenance',
                'description'  => 'Nick live system monthly maintenance',
                'amount'       => 120,
                'frequency'    => 'monthly',
                'start_date'   => '2025-01-01',
                'charge_month' => null,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['hosting', 'maintenance'],
                'link'         => 'https://live.globalzsolutions.com',
            ],
            [
                'client_id'    => $mark->id,
                'title'        => 'Mark System Maintenance',
                'description'  => 'Mark system monthly maintenance',
                'amount'       => 80,
                'frequency'    => 'monthly',
                'start_date'   => '2022-01-01',
                'charge_month' => null,
                'active'       => true,
                'archived'     => false,
                'tags'         => ['hosting', 'maintenance'],
                'link'         => 'https://localhost.com',
            ],
        ];

        foreach ($fixedServices as $s) {
            $isActive = $s['active'] && !$s['archived'];
            $status   = $this->resolveStatus($s['active'], $s['archived']);

            $create([
                'client_id'         => $s['client_id'],
                'title'             => $s['title'],
                'description'       => $s['description'],
                'amount'            => $s['amount'],
                'frequency'         => $s['frequency'],
                'start_date'        => $s['start_date'],
                'next_billing_date' => $this->nextBillingDate($s['frequency'], $isActive, $s['charge_month']),
                'status'            => $status,
                'is_active'         => $isActive,
                'billing_count'     => 0,
                'tags'              => $s['tags'],
                'metadata'          => ['link' => $s['link']],
            ]);
        }

        // =====================================================================
        // SERVER FEES — David (CLI001)
        // amount = price + server (whichever is non-zero)
        // Active fees are treated as monthly recurring; inactive as one-time
        // =====================================================================
        $serverFees = [
            [
                'title'       => 'Scarabay.com Server Charges',
                'description' => 'Digital Ocean server charges — single system 2GB RAM',
                'amount'      => 15,
                'active'      => false,
                'archived'    => false,
                'link'        => 'https://scarabay.com',
            ],
            [
                'title'       => 'Server Upgrade — luigidarbouazza',
                'description' => 'Upgraded server for luigidarbouazza.com',
                'amount'      => 50,
                'active'      => false,
                'archived'    => false,
                'link'        => 'https://luigidarbouazza.com',
            ],
            [
                'title'       => 'Email Security — luigidarbouazza',
                'description' => 'Email security for luigidarbouazza.com',
                'amount'      => 10,
                'active'      => true,
                'archived'    => false,
                'link'        => 'https://luigidarbouazza.com',
            ],
            [
                'title'       => 'Hostinger Auto Backup (Accounts)',
                'description' => 'Hostinger auto backup for accounts system',
                'amount'      => 25,
                'active'      => true,
                'archived'    => false,
                'link'        => '#',
            ],
            [
                'title'       => 'Hostinger Auto Backup (Luigi)',
                'description' => 'Hostinger auto backup for luigi system',
                'amount'      => 25,
                'active'      => true,
                'archived'    => false,
                'link'        => '#',
            ],
            [
                'title'       => 'Western Union Charges',
                'description' => 'Western Union transfer charges',
                'amount'      => 14,
                'active'      => false,
                'archived'    => false,
                'link'        => '#',
            ],
        ];

        foreach ($serverFees as $s) {
            $isActive  = $s['active'] && !$s['archived'];
            $status    = $this->resolveStatus($s['active'], $s['archived']);
            $frequency = $isActive ? 'monthly' : 'one-time';

            $create([
                'client_id'         => $david->id,
                'title'             => $s['title'],
                'description'       => $s['description'],
                'amount'            => $s['amount'],
                'frequency'         => $frequency,
                'start_date'        => '2022-01-01',
                'next_billing_date' => $isActive ? '2026-03-01' : null,
                'status'            => $status,
                'is_active'         => $isActive,
                'billing_count'     => 0,
                'tags'              => ['server', 'infrastructure', 'fixed'],
                'metadata'          => ['link' => $s['link']],
            ]);
        }
    }
}
