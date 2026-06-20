<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionHistoryRequest extends FormRequest
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
        return [
            'transaction_id' => 'required|string|max:100',
            'device_id' => 'required|string|max:150',
            'msisdn' => 'required|string|max:50',
            'op_completion_time' => 'required',
            'operations' => 'required|string|max:150',
            'devices_upload' => 'required|integer|min:0',
            'device_prosses' => 'required|integer|min:0',
            'device_update' => 'required|integer|min:0',
            'last_update' => 'required',
            'status' => 'required|string|max:50',
            'company_id' => backpack_user()->hasRole('Super Admin') ? 'required|exists:companies,id' : 'nullable',
        ];
    }
}
