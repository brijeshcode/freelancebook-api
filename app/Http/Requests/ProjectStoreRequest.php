<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;

class ProjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'budget_currency' => ['required_with:budget', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'project_details' => ['nullable', 'string'],
            // 'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            // 'end_date' => ['nullable', 'date', 'after:start_date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'total_paid' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'payment_currency' => ['required_with:total_paid', 'string', 'size:3'],
            'status' => ['required', 'string', 'in:prospective,planned,active,completed,on_hold,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'The selected client does not exist.',
            'budget_currency.required_with' => 'Budget currency is required when budget is provided.',
            'payment_currency.required_with' => 'Payment currency is required when total paid is provided.',
            'end_date.after' => 'End date must be after start date.',
            'deadline.after_or_equal' => 'Deadline must be today or a future date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        ApiResponse::failValidation($validator->errors());
    }
}