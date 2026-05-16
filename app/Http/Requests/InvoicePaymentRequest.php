<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoicePaymentRequest extends FormRequest
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
        return [
            'date_transaction' => 'required|date',
            'status' => 'required|in:enter,out',
            'nominal_transaction' => 'required',
            'cast_account_id' => 'required|exists:cast_accounts,id',
            'kdp' => 'required',
            'no_invoice' => 'required',
            // 'company_id' => 'required',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'date_transaction' => trans('backpack::crud.cash_account.field_transaction.date_transaction.label'),
            'status' => trans('backpack::crud.cash_account.field_transaction.status.label'),
            'nominal_transaction' => trans('backpack::crud.cash_account.field_transaction.nominal_transaction.label'),
            'account_id' => trans('backpack::crud.cash_account.field_transaction.account_id.label'),
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
