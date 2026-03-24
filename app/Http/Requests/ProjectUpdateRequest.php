<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'        => ['sometimes', 'exists:clients,id'],
            'name'             => ['sometimes', 'string', 'max:255'],
            'budget'           => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'currency_id'      => ['sometimes', 'exists:currencies,id'],
            'notes'            => ['nullable', 'string'],
            'project_details'  => ['nullable', 'string'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date', 'after:start_date'],
            'deadline'         => ['nullable', 'date'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'actual_hours'     => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'total_paid'       => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'status'           => ['sometimes', 'string', 'in:prospective,planned,active,completed,on_hold,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists'   => 'The selected client does not exist.',
            'currency_id.exists' => 'The selected currency does not exist.',
            'end_date.after'     => 'End date must be after start date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        ApiResponse::failValidation($validator->errors());
    }
}
