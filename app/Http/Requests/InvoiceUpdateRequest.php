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
            'client_id'    => 'sometimes|exists:clients,id',
            'project_id'   => 'nullable|exists:projects,id',
            'invoice_date'  => 'sometimes|date',
            'billing_month' => 'nullable|date',
            'due_date'      => 'nullable|date|after:invoice_date',
            'notes'        => 'nullable|string|max:1000',
            'status'       => 'sometimes|in:draft,sent,paid,overdue,cancelled',

            'currency_id'      => 'sometimes|exists:currencies,id',
            'exchange_rate'    => 'nullable|numeric|min:0.000001',
            'calculation_type' => 'nullable|in:multiply,divide',

            'tax_rate'  => 'nullable|numeric|min:0|max:100',
            'tax_label' => 'nullable|string|max:50',

            // Items (optional on update — send full list to replace, omit to keep existing)
            'items'                              => 'sometimes|array|min:1',
            'items.*.id'                         => 'nullable|exists:invoice_items,id',
            'items.*.service_id'                 => 'nullable|exists:services,id',
            'items.*.item_type'                  => 'sometimes|in:service,custom,expense,milestone',
            'items.*.title'                      => 'nullable|string|max:255',
            'items.*.description'                => 'nullable|string|max:1000',
            'items.*.quantity'                   => 'required_with:items|integer|min:1',
            'items.*.unit_price'                 => 'required_with:items|numeric|min:0',
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
            'currency_id.exists'           => 'Selected currency does not exist.',
            'items.*.item_type.in'         => 'Item type must be service, custom, expense, or milestone.',
            'items.*.description.required_with' => 'Item description is required.',
            'items.*.quantity.required_with'    => 'Item quantity is required.',
            'items.*.unit_price.required_with'  => 'Item unit price is required.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        ApiResponse::failValidation($validator->errors());
    }
}
