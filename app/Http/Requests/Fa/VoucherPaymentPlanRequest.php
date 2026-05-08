<?php

namespace App\Http\Requests\Fa;

use App\Models\PaymentVoucher;
use Illuminate\Foundation\Http\FormRequest;

class VoucherPaymentPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'voucher' => [
                'required',
                'array',
                'min:1',
                function ($attr, $value, $fail) {
                    foreach ($value as $id_voucher) {
                        $payment_voucher = PaymentVoucher::where('voucher_id', $id_voucher)->first();
                        if ($payment_voucher != null) {
                            $fail(trans('backpack::crud.voucher_payment.voucher_payment_exists'));
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get the validation attributes.
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [];
    }
}
