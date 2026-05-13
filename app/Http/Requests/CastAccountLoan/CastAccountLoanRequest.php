<?php

namespace App\Http\Requests\CastAccountLoan;

use Illuminate\Foundation\Http\FormRequest;

class CastAccountLoanRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $id = $this->id ?? '';
        return [
            'name' => 'required|max:100|unique:cast_accounts,name,' . $id,
            'bank_name' => 'required|max:50',
            'no_account' => 'required|max:100|unique:cast_accounts,no_account,' . $id,
            'account_id' => 'required|exists:accounts,id',
            'total_saldo' => 'required|numeric|min:0',
            'status' => 'required|in:loan',
            'date_transaction_init' => 'nullable|date',
        ];
    }
}
