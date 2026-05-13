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
        public readonly ?string $description
    ) {}

    public static function fromRequest(Request $request): self
    {
        $cleanNominal = fn($val) => (float) str_replace('.', '', $val ?? '0');

        return new self(
            cast_account_id: (int) $request->cast_account_id,
            to_account: $request->to_account,
            nominal_transfer: $cleanNominal($request->nominal_transfer),
            date_move_balance: $request->date_move_balance,
            description: $request->description
        );
    }
}
