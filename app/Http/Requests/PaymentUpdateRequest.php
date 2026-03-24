<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;

class PaymentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'             => 'sometimes|exists:clients,id',
            'amount'                => 'sometimes|numeric|min:0.01',
            'currency_id'           => 'sometimes|exists:currencies,id',
            'exchange_rate'         => 'nullable|numeric|min:0.000001',
            'calculation_type'      => 'nullable|in:multiply,divide',
            'payment_date'          => 'sometimes|date|before_or_equal:today',
            'payment_method'        => 'sometimes|in:bank_transfer,paypal,stripe,western_union,cash,check,crypto,other',
            'transaction_reference' => 'nullable|string|max:100',
            'notes'                 => 'nullable|string|max:1000',
            'status'                => 'sometimes|in:pending,completed,failed,refunded',
            'receipt_attachments'   => 'nullable|array',
            'receipt_attachments.*' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'currency_id.exists'    => 'Selected currency does not exist.',
            'amount.min'            => 'Payment amount must be greater than 0.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
            'payment_method.in'     => 'Invalid payment method selected.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        ApiResponse::failValidation($validator->errors());
    }
}
