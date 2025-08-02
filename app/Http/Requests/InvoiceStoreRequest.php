<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'notes' => 'nullable|string|max:1000',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0.000001|max:9999.999999',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_label' => 'nullable|string|max:50',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.service_period_start' => 'nullable|date',
            'items.*.service_period_end' => 'nullable|date|after_or_equal:items.*.service_period_start',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Client is required',
            'client_id.exists' => 'Selected client does not exist',
            'items.required' => 'At least one invoice item is required',
            'items.*.description.required' => 'Item description is required',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'items.*.unit_price.min' => 'Unit price must be non-negative',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        ApiResponse::failValidation($validator->errors());
    }
}