<?php

namespace App\DTOs\CastAccountLoan;

use Illuminate\Http\Request;
use App\Models\CastAccount;

class CastAccountLoanSaveData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $bank_name,
        public readonly ?string $no_account,
        public readonly ?int $account_id,
        public readonly float $total_saldo,
        public readonly string $status,
        public readonly ?string $date_transaction_init = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        $cleanNominal = fn($val) => (float) str_replace('.', '', $val ?? '0');

        return new self(
            id: $request->id ? (int) $request->id : null,
            name: $request->name,
            bank_name: $request->bank_name,
            no_account: $request->no_account,
            account_id: $request->account_id ? (int) $request->account_id : null,
            total_saldo: $cleanNominal($request->total_saldo),
            status: $request->status ?? CastAccount::LOAN,
            date_transaction_init: $request->date_transaction_init
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'no_account' => $this->no_account,
            'account_id' => $this->account_id,
            'total_saldo' => $this->total_saldo,
            'status' => $this->status,
        ], fn($v) => $v !== null);
    }
}
