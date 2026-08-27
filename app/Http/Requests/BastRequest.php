<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BastRequest extends FormRequest
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
            'pic' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'reference_type' => 'required|in:client_po,proforma_invoice',
            'client_po_id' => 'required_if:reference_type,client_po|nullable|exists:client_po,id',
            'proforma_invoice_client_id' => 'required_if:reference_type,proforma_invoice|nullable|exists:proforma_invoice_clients,id',
            'first_party' => 'required|string|max:255',
            'first_party_address' => 'required|string',
            'address' => 'required|string',
            'date' => 'required|date',
            'number' => 'required|string|max:100|unique:basts,number,' . $id . ',id,deleted_at,NULL',
            'description' => 'required|string',
            'qty' => 'required|integer|min:1',
            'information' => 'nullable|string',
        ];

        if (backpack_user() && backpack_user()->canAccessAllCompanies()) {
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
        return [];
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [];
    }
}
