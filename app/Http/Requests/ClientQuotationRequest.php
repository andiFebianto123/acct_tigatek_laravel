<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

class ClientQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request('id');
        $status = request('status');
        
        $rule_origin = [
            'company_id' => 'sometimes|required|exists:companies,id',
            'client_id' => 'required|exists:clients,id',
            'work_code' => 'required|max:30|unique:client_quotations,work_code,' . $id,
            'po_number' => 'required|max:30|unique:client_quotations,po_number,' . $id,
            'job_name' => 'required|max:255',
            'job_value' => 'required|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'reimburse_type' => 'required|max:50',
            'document_path' => ValidUpload::field('nullable')->file('mimes:pdf|max:35840'),
            'date_invoice' => 'nullable|date',
            'rap_value' => 'required|numeric',
            'load_general_value' => 'nullable|numeric',
            'category' => 'required',
        ];

        $rule_no_po = [
            'company_id' => 'sometimes|required|exists:companies,id',
            'work_code' => 'required|max:30|unique:client_quotations,work_code,' . $id,
            'client_id' => 'nullable|exists:clients,id',
            'job_name' => 'nullable|max:255',
            'rap_value' => 'nullable|numeric',
            'job_value' => 'nullable|numeric',
            'tax_ppn' => 'nullable|numeric',
            'reimburse_type' => 'nullable|max:50',
            'load_general_value' => 'nullable|numeric',
            'document_path' => ValidUpload::field('nullable')->file('mimes:pdf|max:35840'),
            'category' => 'nullable',
        ];

        $rule = $rule_no_po;

        if ($status == 'TANPA PO') {
            $rule = $rule_no_po;
            $rule['po_number'] = 'nullable|max:30';
        } else if ($status == 'ADA PO') {
            $rule = $rule_origin;
        }

        if (request()->has('work_code')) {
            $rule['work_code'] = 'required|max:30|unique:client_quotations,work_code,' . $id;
        } else {
            $rule['work_code'] = 'nullable|max:30|unique:client_quotations,work_code,' . $id;
        }

        $rule['date_po'] = 'nullable|date';

        return $rule;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
