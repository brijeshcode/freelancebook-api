<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'client_code',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_number',
        'notes',
        'status',
        'billing_preferences',
        'user_id',
    ];

    protected $casts = [
        'billing_preferences' => 'array',
        'total_billed' => 'decimal:2',
        'total_received' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices():HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments():HasMany
    {
        return $this->hasMany(Payment::class);
    }
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Generate unique client code
    public static function generateClientCode(): string
    {
        $lastClient = self::withTrashed()->orderBy('id', 'desc')->first();
        $number = $lastClient ? $lastClient->id + 1 : 1;
        return 'CLI' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}