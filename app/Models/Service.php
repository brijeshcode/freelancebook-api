<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'project_id',
        'created_by',
        'title',
        'description',
        'amount',
        'currency',
        'has_tax',
        'tax_name',
        'tax_rate',
        'tax_type',
        'frequency',
        'start_date',
        'next_billing_date',
        'end_date',
        'status',
        'is_active',
        'last_billed_invoice_id',
        'last_billed_at',
        'billing_count',
        'tags',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'has_tax' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'end_date' => 'date',
        'last_billed_at' => 'datetime',
        'billing_count' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // public function lastBilledInvoice(): BelongsTo
    // {
    //     return $this->belongsTo(Invoice::class, 'last_billed_invoice_id');
    // }

    // Tax Calculations
    public function getBaseAmount(): float
    {
        if (!$this->has_tax) {
            return (float) $this->amount;
        }

        if ($this->tax_type === 'inclusive') {
            return (float) ($this->amount / (1 + ($this->tax_rate / 100)));
        }

        return (float) $this->amount;
    }

    public function getTaxAmount(): float
    {
        if (!$this->has_tax) {
            return 0.00;
        }

        $baseAmount = $this->getBaseAmount();
        return (float) ($baseAmount * ($this->tax_rate / 100));
    }

    public function getTotalAmount(): float
    {
        return $this->getBaseAmount() + $this->getTaxAmount();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecurring($query)
    {
        return $query->whereNot('frequency', 'one-time');
    }

    public function scopeOneTime($query)
    {
        return $query->where('frequency', 'one-time');
    }

    public function scopeReadyForBilling($query)
    {
        return $query->where('status', 'active')
                    ->where('is_active', true)
                    ->whereNotNull('next_billing_date')
                    ->where('next_billing_date', '<=', now());
    }
}