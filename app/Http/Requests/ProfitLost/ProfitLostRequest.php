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
            // Determine if the type is supplier based on input fields
            $isSupplier = $this->has('supplier_invoice_id') || $this->input('orderable_type') === 'App\\Models\\InvoiceClient';

            if (!$isSupplier) {
                return [
                    'work_code' => [
                        'required',
                        Rule::unique('project_profit_lost', 'orderable_id')
                            ->where('orderable_type', 'App\\Models\\ClientPo')
                            ->ignore($this->id)
                    ],
                    'category' => 'required',
                    'company_id' => 'required|exists:companies,id',
                    'price_after_year' => 'nullable',
                    'price_general' => 'nullable',
                ];
            } else {
                return [
                    'purchase_order_id' => 'nullable',
                    'supplier_invoice_id' => 'nullable',
                    'category' => 'nullable',
                    'company_id' => 'required|exists:companies,id',
                    'price_after_year' => 'nullable',
                    'price_general' => 'nullable',
                ];
            }
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
