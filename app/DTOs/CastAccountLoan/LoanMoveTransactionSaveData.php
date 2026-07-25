<?php

namespace App\DTOs\CastAccountLoan;

use Illuminate\Http\Request;

class LoanMoveTransactionSaveData
{
    public function __construct(
        public readonly int $cast_account_id,
        public readonly float $payment_price,
        public readonly int $loan_transaction_flag_id,
        public readonly ?string $description,
        public readonly string $date_loan_transaction,
        public readonly int $cast_account_destination_id,
        public readonly ?string $currency_code = 'IDR'
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currencyCode = request()->input('currency_code')
            ?? $request->currency_code
            ?? $request->payment_price_currency
            ?? 'IDR';
        $rawNominal = (string) ($request->payment_price ?? '0');
        $cleanNominal = ($currencyCode === 'USD')
            ? (float) str_replace(',', '', $rawNominal)
            : (float) str_replace('.', '', $rawNominal);

        return new self(
            cast_account_id: (int) $request->cast_account_id,
            payment_price: $cleanNominal,
            loan_transaction_flag_id: (int) $request->loan_transaction_flag_id,
            description: $request->description,
            date_loan_transaction: $request->date_loan_transaction,
            cast_account_destination_id: (int) $request->cast_account_destination_id,
            currency_code: $currencyCode
        );
    }

    public function toArray(): array
    {
        return [
            'cast_account_id' => $this->cast_account_id,
            'payment_price' => $this->payment_price,
            'loan_transaction_flag_id' => $this->loan_transaction_flag_id,
            'description' => $this->description,
            'date_loan_transaction' => $this->date_loan_transaction,
            'cast_account_destination_id' => $this->cast_account_destination_id,
            'currency_code' => $this->currency_code,
        ];
    }
}
