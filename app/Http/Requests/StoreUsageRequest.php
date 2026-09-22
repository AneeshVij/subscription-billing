<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'merchant_id' => [
                'required',
                'integer',
                'exists:merchants,id',
            ],

            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],

            'usage_date' => [
                'required',
                'date',
            ],

            'units' => [
                'required',
                'integer',
                'min:1',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:255',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }
}