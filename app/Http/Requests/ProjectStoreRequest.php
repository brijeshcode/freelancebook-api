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
            'client_id'        => ['required', 'exists:clients,id'],
            'name'             => ['required', 'string', 'max:255'],
            'budget'           => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency_id'      => ['required_with:budget', 'exists:currencies,id'],
            'notes'            => ['nullable', 'string'],
            'project_details'  => ['nullable', 'string'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date', 'after:start_date'],
            'deadline'         => ['nullable', 'date', 'after_or_equal:today'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'actual_hours'     => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'total_paid'       => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'status'           => ['required', 'string', 'in:prospective,planned,active,completed,on_hold,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists'       => 'The selected client does not exist.',
            'currency_id.exists'     => 'The selected currency does not exist.',
            'currency_id.required_with' => 'Currency is required when a budget is provided.',
            'end_date.after'         => 'End date must be after start date.',
            'deadline.after_or_equal' => 'Deadline must be today or a future date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        ApiResponse::failValidation($validator->errors());
    }
}
