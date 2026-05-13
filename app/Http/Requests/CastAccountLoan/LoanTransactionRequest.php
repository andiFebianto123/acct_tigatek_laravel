<?php

namespace App\Http\Requests\CastAccountLoan;

use App\Models\CastAccount;
use App\Models\AccountTransaction;
use App\Http\Helpers\CustomHelper;
use Illuminate\Foundation\Http\FormRequest;

class LoanTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $cast_account_id = $this->cast_account_id;
        $status = $this->status;
        $cast_account_destination_id = $this->cast_account_destination_id;

        $rules = [
            'date_transaction' => 'required',
            'nominal_transaction' => [
                'required',
                'numeric',
                'min:1000',
                function ($attribute, $value, $fail) use ($cast_account_id, $status, $cast_account_destination_id) {
                    if ($status == CastAccount::ENTER) {
                        $balance = CustomHelper::total_balance_cast_account($cast_account_destination_id, CastAccount::CASH);
                        if ($value > $balance) {
                            $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_transfer_to_more"));
                        }
                    } else if ($status == CastAccount::OUT) {
                        $balance = CustomHelper::total_balance_cast_account($cast_account_id, CastAccount::LOAN);
                        if ($value > $balance) {
                            $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_transfer_to_more"));
                        }
                    }
                }
            ],
            'cast_account_destination_id' => 'required|exists:cast_accounts,id',
            'kdp' => 'max:50',
            'job_name' => 'max:100',
            'no_invoice' => 'max:100',
            'account_id' => 'exists:accounts,id',
            'status' => [
                'nullable',
                'in:enter,out',
                function ($attr, $value, $fail) use ($cast_account_destination_id) {
                    if ($cast_account_destination_id == AccountTransaction::BANK_LOAN) {
                        if ($value != CastAccount::ENTER) {
                            $fail(trans('backpack::crud.cash_account_loan.field.cast_account_destination_id.bank_loan_alert'));
                        }
                    }
                }
            ],
        ];

        if ($cast_account_destination_id == AccountTransaction::BANK_LOAN) {
            $rules['cast_account_destination_id'] = 'required|in:' . AccountTransaction::BANK_LOAN;
            $rules['nominal_transaction'] = 'required|numeric|min:1000';
        }

        return $rules;
    }
}
