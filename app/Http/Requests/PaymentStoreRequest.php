<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;

class PaymentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0.000001|max:9999.999999',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:bank_transfer,paypal,stripe,western_union,cash,check,crypto,other',
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:pending,completed,failed,refunded',
            'receipt_attachments' => 'nullable|array',
            'receipt_attachments.*' => 'string|max:255', // File paths
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Client is required',
            'client_id.exists' => 'Selected client does not exist',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be greater than 0',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future',
            'payment_method.in' => 'Invalid payment method selected',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        ApiResponse::failValidation($validator->errors());
    }
}