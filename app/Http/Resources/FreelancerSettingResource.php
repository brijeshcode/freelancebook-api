<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreelancerSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'freelancer_id' => $this->freelancer_id,
            
            // Currency & Financial
            'base_currency' => $this->base_currency,
            'invoice_due_days' => $this->invoice_due_days,
            
            // Invoice Settings
            'invoice_prefix' => $this->invoice_prefix,
            'next_invoice_number' => $this->next_invoice_number,
            'invoice_year' => $this->invoice_year,
            'invoice_footer' => $this->invoice_footer,
            'invoice_branding' => $this->invoice_branding,
            
            // Tax Settings
            'default_tax_rate' => $this->default_tax_rate,
            'default_tax_label' => $this->default_tax_label,
            'tax_number' => $this->tax_number,
            
            // Business Information
            'business_address' => $this->business_address,
            'business_phone' => $this->business_phone,
            'business_email' => $this->business_email,
            'business_website' => $this->business_website,
            
            // Notification Settings
            'notification_preferences' => $this->notification_preferences ?? [
                'invoice_reminders' => true,
                'payment_notifications' => true,
                'renewal_alerts' => true,
                'email_notifications' => true
            ],
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}