<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;

class InvoiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'sometimes|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'nullable|date|after:invoice_date',
            'notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'currency' => 'sometimes|string|size:3',
            'exchange_rate' => 'sometimes|numeric|min:0.000001|max:9999.999999',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_label' => 'nullable|string|max:50',
            
            // Items (optional for updates)
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.service_period_start' => 'nullable|date',
            'items.*.service_period_end' => 'nullable|date|after_or_equal:items.*.service_period_start',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        ApiResponse::failValidation($validator->errors());
    }
}