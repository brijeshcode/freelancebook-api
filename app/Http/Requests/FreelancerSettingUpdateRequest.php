<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FreelancerSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_currency' => 'sometimes|string|size:3',
            'invoice_due_days' => 'sometimes|integer|min:1|max:365',
            'invoice_prefix' => 'sometimes|string|max:10',
            'invoice_footer' => 'sometimes|nullable|string|max:1000',
            'invoice_branding' => 'sometimes|nullable|json',
            // 'invoice_branding.*' => 'sometimes|string', // Validate array values if present
            'default_tax_rate' => 'sometimes|numeric|min:0|max:100',
            'default_tax_label' => 'sometimes|nullable|string|max:50',
            'tax_number' => 'sometimes|nullable|string|max:50',
            'business_address' => 'sometimes|nullable|string|max:500',
            'business_phone' => 'sometimes|nullable|string|max:20',
            'business_email' => 'sometimes|nullable|email|max:255',
            'business_website' => 'sometimes|nullable|url|max:255',
            'notification_preferences' => 'sometimes|nullable|json',
            'notification_preferences.invoice_reminders' => 'sometimes|boolean',
            'notification_preferences.payment_notifications' => 'sometimes|boolean',
            'notification_preferences.renewal_alerts' => 'sometimes|boolean',
            'notification_preferences.email_notifications' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'base_currency.size' => 'Currency code must be exactly 3 characters (e.g., USD, EUR)',
            'invoice_due_days.min' => 'Invoice due days must be at least 1 day',
            'invoice_due_days.max' => 'Invoice due days cannot exceed 365 days',
            'default_tax_rate.max' => 'Tax rate cannot exceed 100%',
            'business_email.email' => 'Please provide a valid email address',
            'business_website.url' => 'Please provide a valid website URL',
        ];
    }
}