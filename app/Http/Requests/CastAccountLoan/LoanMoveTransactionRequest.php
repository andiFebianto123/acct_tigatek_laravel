<?php

namespace App\Http\Requests\CastAccountLoan;

use App\Models\CastAccount;
use App\Models\AccountTransaction;
use App\Models\LoanTransactionFlag;
use App\Http\Helpers\CustomHelper;
use Illuminate\Foundation\Http\FormRequest;

class LoanMoveTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $loan_transaction_flag_id = $this->loan_transaction_flag_id;
        $cast_account_destination_id = $this->cast_account_destination_id;

        return [
            'loan_transaction_flag_id' => 'required|exists:loan_transaction_flags,id',
            'date_loan_transaction' => 'required|date',
            'cast_account_destination_id' => [
                'required',
                'exists:cast_accounts,id',
            ],
            'payment_price' => [
                'required',
                'numeric',
                'min:1000',
                function ($attribute, $value, $fail) use ($loan_transaction_flag_id, $cast_account_destination_id) {
                    $cast_account_destination = CastAccount::find($cast_account_destination_id);
                    if (!$cast_account_destination) return;

                    $total_balance_destination = CustomHelper::balanceAccount($cast_account_destination->account->code);

                    if ($total_balance_destination < $value) {
                        $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_transfer_to_more_destination"));
                    }

                    $total_loan_transaction = AccountTransaction::where("reference_id", $loan_transaction_flag_id)
                        ->whereNull('cast_account_destination_id')
                        ->where("reference_type", LoanTransactionFlag::class)
                        ->where('status', CastAccount::OUT)
                        ->sum('nominal_transaction');

                    $loan_transaction_flag = LoanTransactionFlag::find($loan_transaction_flag_id);
                    if (!$loan_transaction_flag) return;

                    $remaining_balance = $loan_transaction_flag->total_price - $total_loan_transaction - $value;
                    if ($remaining_balance < 0) {
                        $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_payment_to_more"));
                    }
                }
            ],
        ];
    }
}
