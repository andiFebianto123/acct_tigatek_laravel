<?php

namespace App\Http\Requests\CastAccount;

use App\Models\CastAccount;
use App\Http\Helpers\CustomHelper;
use Illuminate\Foundation\Http\FormRequest;

class TransferBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Akses dikontrol oleh Backpack di controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $cast_account_id = $this->cast_account_id;
        $balance = CustomHelper::total_balance_cast_account($cast_account_id, CastAccount::CASH);

        return [
            'cast_account_id' => ['required', 'exists:cast_accounts,id'],
            'to_account' => [
                'required',
                function ($attr, $value, $fail) use ($cast_account_id) {
                    $sourceAccount = CastAccount::find($cast_account_id);
                    if (strpos($value, 'acc_') === 0) {
                        $accountId = str_replace('acc_', '', $value);
                        if (!\App\Models\Account::where('id', $accountId)->exists()) {
                            $fail(trans('backpack::crud.cash_account.field_transfer.errors.account_not_found'));
                        }
                    } else {
                        $destAccount = CastAccount::where('id', $value)->first();
                        if (!$destAccount) {
                            $fail(trans('backpack::crud.cash_account.field_transfer.errors.cast_account_not_found'));
                        } else {
                            if ($value == $cast_account_id) {
                                $fail(trans('backpack::crud.cash_account.field_transfer.errors.to_account_is_same'));
                            }
                        }
                    }
                }
            ],
            'currency_code' => [
                'nullable',
                'in:IDR,USD',
                function ($attribute, $value, $fail) use ($cast_account_id) {
                    if ($cast_account_id) {
                        $sourceAccount = CastAccount::find($cast_account_id);
                        if ($sourceAccount && !empty($sourceAccount->currency_code)) {
                            $inputCurrency = $value ?? $this->nominal_transfer_currency ?? 'IDR';
                            if ($inputCurrency !== $sourceAccount->currency_code) {
                                $fail(trans('backpack::crud.cash_account.field_transfer.errors.transfer_currency_mismatch', [
                                    'account_name' => $sourceAccount->name,
                                    'account_currency' => $sourceAccount->currency_code,
                                ]));
                            }
                        }
                    }
                }
            ],
            'nominal_transfer' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($balance) {
                    if ($value > $balance) {
                        $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_transfer_to_more"));
                    }
                }
            ],
        ];
    }
}
