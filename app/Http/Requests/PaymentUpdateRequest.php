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
            'client_id' => 'sometimes|exists:clients,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'exchange_rate' => 'sometimes|numeric|min:0.000001|max:9999.999999',
            'payment_date' => 'sometimes|date|before_or_equal:today',
            'payment_method' => 'sometimes|in:bank_transfer,paypal,stripe,western_union,cash,check,crypto,other',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:pending,completed,failed,refunded',
            'receipt_attachments' => 'nullable|array',
            'receipt_attachments.*' => 'string|max:255',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        ApiResponse::failValidation($validator->errors());
    }
}