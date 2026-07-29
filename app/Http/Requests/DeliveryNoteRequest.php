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
            'client_id'         => 'required|exists:clients,id',
            'invoice_client_id' => 'nullable|exists:invoice_clients,id',
            'client_po_id'      => 'nullable|exists:client_po,id',
            'reference_type'    => 'nullable|in:quotation,proforma_invoice,client_po,invoice_client',
            'reference_id'      => 'nullable|integer|min:1',
            'address'           => 'nullable|string',
            'date'              => 'required|date',
            'number'            => 'required|string|max:50|unique:delivery_notes,number,' . $id,
            'description'       => 'nullable|string',
            'qty'               => 'nullable|integer',
            'information'       => 'nullable|string',
            'delivery_note_details' => 'nullable|array',
            'delivery_note_details.*.device_stock_id' => 'nullable|exists:device_stocks,id',
            'delivery_note_details.*.description' => 'nullable|string',
            'delivery_note_details.*.qty' => 'required_with:delivery_note_details|integer|min:1',
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
