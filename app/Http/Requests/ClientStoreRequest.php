<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,company',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,archived',
            'billing_preferences' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Client name is required',
            'type.required' => 'Client type is required',
            'type.in' => 'Client type must be individual or company',
            'email.email' => 'Please provide a valid email address',
            'website.url' => 'Please provide a valid website URL',
        ];
    }
}