<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\User;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Base currency: INR
     *
     * Rates convert 1 unit of each currency → INR.
     *
     *   multiply → inr = amount * rate   (rate = "how many INR per 1 unit")
     *   divide   → inr = amount / rate   (rate = "how many units per 1 INR" — used when
     *                                     the foreign currency is much weaker, e.g. LBP)
     */
    public function run(): void
    {
        $adminId = User::first()?->id;

        $currencies = [
            [
                'code'             => 'INR',
                'name'             => 'Indian Rupee',
                'symbol'           => '₹',
                'decimal_places'   => 2,
                'rate'             => 1.000000,
                'calculation_type' => 'multiply', // base currency — no conversion
            ],
            [
                'code'             => 'USD',
                'name'             => 'US Dollar',
                'symbol'           => '$',
                'decimal_places'   => 2,
                'rate'             => 83.500000,
                'calculation_type' => 'multiply', // 1 USD = ₹83.50
            ],
            [
                'code'             => 'EUR',
                'name'             => 'Euro',
                'symbol'           => '€',
                'decimal_places'   => 2,
                'rate'             => 91.200000,
                'calculation_type' => 'multiply', // 1 EUR = ₹91.20
            ],
            [
                'code'             => 'GBP',
                'name'             => 'British Pound',
                'symbol'           => '£',
                'decimal_places'   => 2,
                'rate'             => 106.000000,
                'calculation_type' => 'multiply', // 1 GBP = ₹106.00
            ],
            [
                'code'             => 'AED',
                'name'             => 'UAE Dirham',
                'symbol'           => 'د.إ',
                'decimal_places'   => 2,
                'rate'             => 22.750000,
                'calculation_type' => 'multiply', // 1 AED = ₹22.75  (83.5 / 3.67)
            ],
            [
                'code'             => 'LBP',
                'name'             => 'Lebanese Pound',
                'symbol'           => 'ل.ل',
                'decimal_places'   => 0,
                'rate'             => 1071.856000,
                'calculation_type' => 'divide',   // ₹1 = 1071.856 LBP → inr = lbp / 1071.856
            ],
            [
                'code'             => 'CAD',
                'name'             => 'Canadian Dollar',
                'symbol'           => 'CA$',
                'decimal_places'   => 2,
                'rate'             => 61.790000,
                'calculation_type' => 'multiply', // 1 CAD = ₹61.79  (0.74 * 83.5)
            ],
            [
                'code'             => 'AUD',
                'name'             => 'Australian Dollar',
                'symbol'           => 'A$',
                'decimal_places'   => 2,
                'rate'             => 54.275000,
                'calculation_type' => 'multiply', // 1 AUD = ₹54.28  (0.65 * 83.5)
            ],
            [
                'code'             => 'JPY',
                'name'             => 'Japanese Yen',
                'symbol'           => '¥',
                'decimal_places'   => 0,
                'rate'             => 0.558000,
                'calculation_type' => 'multiply', // 1 JPY = ₹0.558  (83.5 / 149.5)
            ],
        ];

        foreach ($currencies as $item) {
            $currency = Currency::create([
                'code'           => $item['code'],
                'name'           => $item['name'],
                'symbol'         => $item['symbol'],
                'decimal_places' => $item['decimal_places'],
                'is_active'      => true,
            ]);

            CurrencyRate::create([
                'currency_id'      => $currency->id,
                'rate'             => $item['rate'],
                'calculation_type' => $item['calculation_type'],
                'is_active'        => true,
                'created_by'       => $adminId,
                'updated_by'       => $adminId,
            ]);
        }
    }
}
