<?php

namespace App\Models;

use App\Models\Concerns\HasCurrencyConversion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, HasCurrencyConversion;

    protected $fillable = [
        'client_id',
        'freelancer_id',
        'name',
        'budget',
        'currency_id',
        'exchange_rate',
        'calculation_type',
        'budget_base_currency',
        'notes',
        'project_details',
        'start_date',
        'end_date',
        'deadline',
        'estimated_hours',
        'actual_hours',
        'total_paid',
        'total_paid_base_currency',
        'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'budget_base_currency' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_paid_base_currency' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'deadline' => 'date',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    // Accessors
    public function getBudgetExceededAttribute(): bool
    {
        if (!$this->budget) return false;
        return $this->total_paid > $this->budget;
    }

    public function getRemainingBudgetAttribute(): ?float
    {
        if (!$this->budget) return null;
        return $this->budget - $this->total_paid;
    }

    public function getRemainingBudgetBaseCurrencyAttribute(): ?float
    {
        if (!$this->budget_base_currency) return null;
        return $this->budget_base_currency - $this->total_paid_base_currency;
    }

    public function getTimeVarianceAttribute(): ?float
    {
        if (!$this->estimated_hours) return null;
        return $this->actual_hours - $this->estimated_hours;
    }
}