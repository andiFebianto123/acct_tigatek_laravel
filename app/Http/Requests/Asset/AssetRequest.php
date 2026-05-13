<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class AssetRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $rules = [
            'account_id' => 'required|exists:accounts,id',
            'depreciation_account_id' => 'required|exists:accounts,id',
            'expense_account_id' => 'required|exists:accounts,id',
            'description' => 'required|max:150',
            'year_acquisition' => 'required',
            'price_acquisition' => 'required|numeric|min:1000',
            'economic_age' => 'required|numeric',
        ];

        if (backpack_user()->hasRole('Super Admin')) {
            $rules['company_id'] = 'nullable|exists:companies,id';
        }

        return $rules;
    }
}
