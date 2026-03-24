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
            'client_id'    => 'required|exists:clients,id',
            'project_id'   => 'nullable|exists:projects,id',
            'invoice_date'  => 'required|date',
            'billing_month' => 'nullable|date',
            'due_date'      => 'nullable|date|after:invoice_date',
            'notes'        => 'nullable|string|max:1000',
            'status'       => 'sometimes|in:draft,sent,paid,overdue,cancelled',

            // Currency — currency_id is required; exchange_rate is optional (auto-filled from
            // the active currency_rate if omitted, but can be overridden by the user)
            'currency_id'      => 'required|exists:currencies,id',
            'exchange_rate'    => 'nullable|numeric|min:0.000001',
            'calculation_type' => 'nullable|in:multiply,divide',

            'tax_rate'  => 'nullable|numeric|min:0|max:100',
            'tax_label' => 'nullable|string|max:50',

            // Items
            'items'                              => 'required|array|min:1',
            'items.*.service_id'                 => 'nullable|exists:services,id',
            'items.*.item_type'                  => 'sometimes|in:service,custom,expense,milestone',
            'items.*.title'                      => 'nullable|string|max:255',
            'items.*.description'                => 'nullable|string|max:1000',
            'items.*.quantity'                   => 'required|integer|min:1',
            'items.*.unit_price'                 => 'required|numeric|min:0',
            'items.*.service_period_start'       => 'nullable|date',
            'items.*.service_period_end'         => 'nullable|date|after_or_equal:items.*.service_period_start',
            'items.*.is_recurring'               => 'sometimes|boolean',
            'items.*.notes'                      => 'nullable|string|max:500',
            'items.*.sort_order'                 => 'sometimes|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'          => 'Client is required.',
            'client_id.exists'            => 'Selected client does not exist.',
            'currency_id.required'        => 'Currency is required.',
            'currency_id.exists'          => 'Selected currency does not exist.',
            'items.required'              => 'At least one invoice item is required.',
            'items.*.description.required' => 'Item description is required.',
            'items.*.quantity.min'        => 'Quantity must be at least 1.',
            'items.*.unit_price.min'      => 'Unit price must be non-negative.',
            'items.*.item_type.in'        => 'Item type must be service, custom, expense, or milestone.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        ApiResponse::failValidation($validator->errors());
    }
}
