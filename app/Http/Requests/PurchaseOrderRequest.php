<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Backpack\CRUD\app\Library\Validation\Rules\ValidUpload;

class PurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
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

        $currencyCode = request('currency_code', 'IDR');
        $minJobValue = ($currencyCode === 'USD') ? 0.01 : 1000;

        $rule = [
            'po_type' => 'required|in:subkon,supplier',
            'subkon_id' => 'required|exists:subkons,id',
            'po_number' => 'required|string|max:255',
            'date_po' => 'required|string',
            'job_name' => 'required|string|max:255',
            'job_description' => 'required',
            'job_value' => 'required|numeric|min:' . $minJobValue,
            'status' => 'required|in:open,close',
            'document_path' => ValidUpload::field('required')->file('mimes:pdf|max:5000'),
            'company_id' => 'required|exists:companies,id',
            'term' => 'nullable|string',
            'pic' => 'required|string|max:150',
        ];

        if (request()->input('po_type') === 'supplier') {
            $rule['work_code'] = 'nullable|max:30';
            $rule['job_name'] = 'nullable|string|max:255';
            $rule['job_description'] = 'nullable';
        } else {
            if (request()->has('work_code')) {
                $rule['work_code'] = 'required|max:30';
            } else {
                $rule['work_code'] = 'nullable|max:30';
            }
        }

        if (request()->input('po_type') === 'supplier') {
            if ($id) {
                $items = json_decode(request()->input('purchase_order_details_edit'), true);
                if (!empty($items)) {
                    $this->merge(['purchase_order_details_edit' => $items]);
                    $rule['purchase_order_details_edit'] = 'nullable|array';
                    $rule['purchase_order_details_edit.*.reference_id'] = 'required_without:purchase_order_details_edit.*.name';
                    $rule['purchase_order_details_edit.*.name'] = 'nullable|max:120';
                }
            } else {
                $items = json_decode(request()->input('purchase_order_details'), true);
                if (!empty($items)) {
                    $this->merge(['purchase_order_details' => $items]);
                    $rule['purchase_order_details'] = 'nullable|array';
                    $rule['purchase_order_details.*.reference_id'] = 'required_without:purchase_order_details.*.name';
                    $rule['purchase_order_details.*.name'] = 'nullable|max:120';
                }
            }
        }

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
