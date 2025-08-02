<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'has_tax' => ['boolean'],
            'tax_name' => ['nullable', 'string', 'max:255', 'required_if:has_tax,true'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:999.99', 'required_if:has_tax,true'],
            'tax_type' => ['nullable', Rule::in(['inclusive', 'exclusive']), 'required_if:has_tax,true'],
            'frequency' => ['required', Rule::in(['one-time', 'weekly', 'monthly', 'quarterly', 'half-yearly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'next_billing_date' => ['nullable', 'date', 'after:start_date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['required', Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled', 'pending_approval'])],
            'is_active' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_name.required_if' => 'Tax name is required when tax is applied',
            'tax_rate.required_if' => 'Tax rate is required when tax is applied',
            'tax_type.required_if' => 'Tax type is required when tax is applied',
            'next_billing_date.after' => 'Next billing date must be after start date',
            'end_date.after' => 'End date must be after start date',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->frequency === 'one-time') {
            $this->merge(['next_billing_date' => null]);
        }

        if (!$this->has_tax) {
            $this->merge([
                'tax_name' => null,
                'tax_rate' => 0,
                'tax_type' => 'exclusive'
            ]);
        }
    }
}

// ServiceUpdateRequest.php
class ServiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'has_tax' => ['boolean'],
            'tax_name' => ['nullable', 'string', 'max:255', 'required_if:has_tax,true'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:999.99', 'required_if:has_tax,true'],
            'tax_type' => ['nullable', Rule::in(['inclusive', 'exclusive']), 'required_if:has_tax,true'],
            'frequency' => ['sometimes', Rule::in(['one-time', 'weekly', 'monthly', 'quarterly', 'half-yearly', 'yearly'])],
            'start_date' => ['sometimes', 'date'],
            'next_billing_date' => ['nullable', 'date', 'after:start_date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'paused', 'completed', 'cancelled', 'pending_approval'])],
            'is_active' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_name.required_if' => 'Tax name is required when tax is applied',
            'tax_rate.required_if' => 'Tax rate is required when tax is applied',
            'tax_type.required_if' => 'Tax type is required when tax is applied',
            'next_billing_date.after' => 'Next billing date must be after start date',
            'end_date.after' => 'End date must be after start date',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->frequency === 'one-time') {
            $this->merge(['next_billing_date' => null]);
        }

        if (!$this->has_tax) {
            $this->merge([
                'tax_name' => null,
                'tax_rate' => 0,
                'tax_type' => 'exclusive'
            ]);
        }
    }
}