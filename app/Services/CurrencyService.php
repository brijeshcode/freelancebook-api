<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\FreelancerSetting;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class CurrencyService
{
    /**
     * Get the active rate record for a currency.
     *
     * @throws RuntimeException if no active rate exists
     */
    public function getActiveRate(int $currencyId): CurrencyRate
    {
        $rate = CurrencyRate::where('currency_id', $currencyId)
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$rate) {
            $currency = Currency::find($currencyId);
            throw new RuntimeException(
                "No active exchange rate found for currency: {$currency?->code} (id: {$currencyId})"
            );
        }

        return $rate;
    }

    /**
     * Convert an amount in the given currency to the freelancer's base currency.
     *
     * @throws RuntimeException if no active rate exists for the currency
     */
    public function toBaseCurrency(float $amount, int $currencyId, string $baseCurrencyCode): float
    {
        $currency = Currency::find($currencyId);

        // Already in base currency — no conversion needed
        if ($currency?->code === $baseCurrencyCode) {
            return $amount;
        }

        $rate = $this->getActiveRate($currencyId);

        return $this->applyRate($amount, (float) $rate->rate, $rate->calculation_type);
    }

    /**
     * Apply a rate snapshot to convert an amount.
     *
     *   multiply → base = amount * rate  (most currencies)
     *   divide   → base = amount / rate  (high-value currencies like LBP: 1,000,000 / 89,500)
     */
    public function applyRate(float $amount, float $rate, string $calculationType): float
    {
        if ($rate == 0) {
            return $amount;
        }

        return $calculationType === 'divide'
            ? round($amount / $rate, 6)
            : round($amount * $rate, 6);
    }

    /**
     * Build a rate snapshot array to be stored on a transaction record.
     * Call this when creating/updating invoices, payments, projects, services.
     *
     * Returns:
     *   [exchange_rate, calculation_type]
     *
     * @throws RuntimeException if no active rate exists
     */
    public function snapshot(int $currencyId, string $baseCurrencyCode): array
    {
        $currency = Currency::find($currencyId);

        // Base currency — rate is 1, no calculation needed
        if ($currency?->code === $baseCurrencyCode) {
            return [
                'exchange_rate'    => 1.000000,
                'calculation_type' => 'multiply',
            ];
        }

        $rate = $this->getActiveRate($currencyId);

        return [
            'exchange_rate'    => (float) $rate->rate,
            'calculation_type' => $rate->calculation_type,
        ];
    }

    /**
     * Get the freelancer's base currency code from their settings.
     */
    public function getBaseCurrency(int $freelancerId): string
    {
        $settings = FreelancerSetting::where('freelancer_id', $freelancerId)->first();

        if (!$settings?->base_currency) {
            throw new RuntimeException("No base currency configured for freelancer id: {$freelancerId}");
        }

        return $settings->base_currency;
    }

    /**
     * Convenience: full conversion in one call.
     * Fetches the base currency, builds a snapshot, and converts the amount.
     *
     * @throws RuntimeException
     */
    public function convert(float $amount, int $currencyId, int $freelancerId): array
    {
        $baseCurrency = $this->getBaseCurrency($freelancerId);
        $snapshot     = $this->snapshot($currencyId, $baseCurrency);

        return [
            'exchange_rate'        => $snapshot['exchange_rate'],
            'calculation_type'     => $snapshot['calculation_type'],
            'amount_base_currency' => $this->applyRate($amount, $snapshot['exchange_rate'], $snapshot['calculation_type']),
        ];
    }

    /**
     * Deactivate all existing rates for a currency before setting a new one.
     * Enforces "one active rate per currency" at the application level.
     */
    public function deactivatePreviousRates(int $currencyId): void
    {
        CurrencyRate::where('currency_id', $currencyId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
