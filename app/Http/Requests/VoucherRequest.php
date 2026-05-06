<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CastAccount;

class VoucherRequest extends FormRequest
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
        $id = $this->id ?? null;
        $factur_status = $this->factur_status ?? null;
        $payment_status = $this->payment_status ?? null;

        // if ($this->ajax() && $this->has('q')) {
        //     return [];
        // }

        $rules = [
            'no_payment' => 'required|max:150',
            'account_id' => 'required|exists:accounts,id',
            'no_voucher' => 'required|max:120|unique:vouchers,no_voucher,' . $id,
            'date_voucher' => 'required|date',
            'bill_number' => 'required|max:50',
            'bill_date' => 'required|date',
            'date_receipt_bill' => 'required|date',
            'payment_description' => 'required',
            'bill_value' => 'required|numeric',
            'dpp_value' => 'nullable|numeric',
            'due_date' => 'required|date',
            'factur_status' => 'required',
            'payment_type' => 'required|max:50',
            'payment_status' => 'nullable|max:50',
            'priority' => 'required|max:50',
            'account_source_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $account = CastAccount::find($value);
                    if ($account && $account->status == CastAccount::LOAN) {
                        $fail(trans('backpack::crud.voucher.confirm.account_loan'));
                    }
                }
            ],
            'reference_id' => 'nullable',
            'subkon_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $companyId = $this->input('company_id');
                    if ($companyId && $companyId != '') {
                        $exists = \App\Models\Subkon::where('id', $value)
                            ->where('company_id', $companyId)
                            ->exists();
                        if (!$exists) {
                            $fail(trans('backpack::crud.voucher.validation.subkon_id_company_mismatch'));
                        }
                    }
                }
            ],
            'client_po_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $companyId = $this->input('company_id');
                    if ($companyId && $companyId != '') {
                        $exists = \App\Models\ClientPo::where('id', $value)
                            ->where('company_id', $companyId)
                            ->exists();
                        if (!$exists) {
                            $fail(trans('backpack::crud.voucher.validation.client_po_id_company_mismatch'));
                        }
                    }
                }
            ],
            'job_name' => 'nullable',
            'company_id' => 'nullable|exists:companies,id',
        ];

        if ($factur_status == 'ADA') {
            $rules['no_factur'] = 'required';
            $rules['date_factur'] = 'required|date';
        } else {
            $rules['no_factur'] = 'nullable';
            $rules['date_factur'] = 'nullable|date';
        }

        if ($payment_status == 'BAYAR') {
            $rules['payment_date'] = 'required|date';
        } else {
            $rules['payment_date'] = 'nullable|date';
        }

        return $rules;
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
