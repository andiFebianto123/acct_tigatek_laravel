<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? $this->route('id');

        $rules = [
            'client_id' => 'required|exists:clients,id',
            'client_po_id' => 'required|exists:client_po,id',
            'address' => 'required|string',
            'date' => 'required|date',
            'number' => 'required|string|max:50|unique:delivery_notes,number,' . $id,
            'description' => 'required|string',
            'qty' => 'required|integer|min:1',
            'information' => 'nullable|string',
        ];

        if (backpack_user() && backpack_user()->hasRole('Super Admin')) {
            $rules['company_id'] = 'required|exists:companies,id';
        } else {
            $rules['company_id'] = 'nullable|exists:companies,id';
        }

        return $rules;
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            // Can be populated if custom field name displays are desired.
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            // Can be populated if custom error messages are desired.
        ];
    }
}
