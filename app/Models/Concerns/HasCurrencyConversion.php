<?php

namespace App\Models\Concerns;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared currency conversion behaviour for financial models.
 *
 * Requires the model to have:
 *   currency_id, exchange_rate, calculation_type
 */
trait HasCurrencyConversion
{
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Convert any amount in this model's currency to the freelancer's base currency
     * using the rate snapshot stored on this record.
     *
     *   multiply → base = amount * rate   (e.g. USD invoice, base INR: 100 * 83.50 = 8,350)
     *   divide   → base = amount / rate   (e.g. LBP invoice, base USD: 1,000,000 / 89,500 = 11.17)
     */
    public function toBaseCurrency(float $amount): float
    {
        if (!$this->exchange_rate || $this->exchange_rate == 0) {
            return $amount;
        }

        return $this->calculation_type === 'divide'
            ? round($amount / $this->exchange_rate, 6)
            : round($amount * $this->exchange_rate, 6);
    }

    /**
     * Check whether this record uses the freelancer's base currency (no conversion needed).
     */
    public function isBaseCurrency(string $baseCurrencyCode): bool
    {
        return $this->currency?->code === $baseCurrencyCode;
    }
}
