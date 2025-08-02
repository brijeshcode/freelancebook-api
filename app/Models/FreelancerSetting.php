<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreelancerSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'freelancer_id',
        'base_currency',
        'invoice_due_days',
        'invoice_prefix',
        'next_invoice_number',
        'invoice_year',
        'invoice_footer',
        'invoice_branding',
        'default_tax_rate',
        'default_tax_label',
        'tax_number',
        'business_address',
        'business_phone',
        'business_email',
        'business_website',
        'notification_preferences'
    ];

    protected $casts = [
        'invoice_due_days' => 'integer',
        'next_invoice_number' => 'integer',
        'invoice_year' => 'integer',
        'invoice_branding' => 'array',
        'default_tax_rate' => 'decimal:2',
        'notification_preferences' => 'array'
    ];

    // Relationships
    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    // Helper Methods
    public function generateInvoiceNumber(): string
    {
        $currentYear = date('Y');
        
        // Reset counter if year changed
        if ($this->invoice_year != $currentYear) {
            $this->update([
                'invoice_year' => $currentYear,
                'next_invoice_number' => 1
            ]);
        }

        $invoiceNumber = $this->invoice_prefix . '-' . $currentYear . '-' . 
                        str_pad($this->next_invoice_number, 3, '0', STR_PAD_LEFT);

        // Increment for next invoice
        $this->increment('next_invoice_number');

        return $invoiceNumber;
    }
}