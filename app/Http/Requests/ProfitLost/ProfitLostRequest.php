<?php

namespace App\Http\Requests\ProfitLost;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfitLostRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $type = $this->input('type');
        
        if ($type === 'project') {
            return [
                'work_code' => 'required|unique:project_profit_lost,client_po_id,' . $this->id,
                'category' => 'required',
                'company_id' => backpack_user()->hasRole('Super Admin') ? 'required' : 'nullable',
                'price_after_year' => 'nullable',
                'price_general' => 'nullable',
            ];
        }

        // Default for Consolidate Item
        return [
            'account_id' => [
                'required',
                'exists:accounts,id',
                Rule::unique('consolidate_income_account_items', 'account_id')->ignore($this->id)
            ],
            'header_id' => 'required|exists:consolidate_income_headers,id',
        ];
    }
}
