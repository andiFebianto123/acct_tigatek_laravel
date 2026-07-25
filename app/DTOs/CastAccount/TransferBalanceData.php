<?php

namespace App\DTOs\CastAccount;

use Illuminate\Http\Request;

class TransferBalanceData
{
    public function __construct(
        public readonly int $cast_account_id,
        public readonly string $to_account, // can be "acc_ID" or "ID"
        public readonly float $nominal_transfer,
        public readonly ?string $date_move_balance,
        public readonly ?string $description,
        public readonly ?string $currency_code = 'IDR'
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currencyCode = request()->input('currency_code')
            ?? $request->currency_code
            ?? $request->nominal_transfer_currency
            ?? 'IDR';
        $rawVal = (string) ($request->nominal_transfer ?? '0');
        $nominal = ($currencyCode === 'USD')
            ? (float) str_replace(',', '', $rawVal)
            : (float) str_replace('.', '', $rawVal);

        return new self(
            cast_account_id: (int) $request->cast_account_id,
            to_account: $request->to_account,
            nominal_transfer: $nominal,
            date_move_balance: $request->date_move_balance,
            description: $request->description,
            currency_code: $currencyCode
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'cast_account_id' => $this->cast_account_id,
            'to_account' => $this->to_account,
            'nominal_transfer' => $this->nominal_transfer,
            'currency_code' => $this->currency_code,
            'date_move_balance' => $this->date_move_balance,
            'description' => $this->description,
        ], fn($v) => $v !== null);
    }
}
