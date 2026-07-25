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
        public readonly ?string $date_transaction_init = null,
        public readonly ?string $currency_code = 'IDR'
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currencyCode = request()->input('currency_code')
            ?? $request->currency_code
            ?? $request->total_saldo_currency
            ?? 'IDR';
        $rawNominal = (string) ($request->total_saldo ?? '0');
        $cleanNominal = ($currencyCode === 'USD')
            ? (float) str_replace(',', '', $rawNominal)
            : (float) str_replace('.', '', $rawNominal);

        return new self(
            id: $request->id ? (int) $request->id : null,
            name: $request->name,
            bank_name: $request->bank_name,
            no_account: $request->no_account,
            account_id: $request->account_id ? (int) $request->account_id : null,
            total_saldo: $cleanNominal,
            status: $request->status ?? CastAccount::LOAN,
            date_transaction_init: $request->date_transaction_init,
            currency_code: $currencyCode
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
            'currency_code' => $this->currency_code,
        ], fn($v) => $v !== null);
    }
}
