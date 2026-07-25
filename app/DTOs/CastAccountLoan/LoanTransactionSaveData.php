<?php

namespace App\DTOs\CastAccountLoan;

use Illuminate\Http\Request;

class LoanTransactionSaveData
{
    public function __construct(
        public readonly int $cast_account_id,
        public readonly string $date_transaction,
        public readonly float $nominal_transaction,
        public readonly ?int $cast_account_destination_id,
        public readonly ?string $description,
        public readonly ?string $status = 'enter',
        public readonly ?string $kdp = null,
        public readonly ?string $job_name = null,
        public readonly ?string $no_invoice = null,
        public readonly ?int $account_id = null,
        public readonly ?string $currency_code = 'IDR'
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currencyCode = request()->input('currency_code')
            ?? $request->currency_code
            ?? $request->nominal_transaction_currency
            ?? 'IDR';
        $rawNominal = (string) ($request->nominal_transaction ?? '0');
        $cleanNominal = ($currencyCode === 'USD')
            ? (float) str_replace(',', '', $rawNominal)
            : (float) str_replace('.', '', $rawNominal);

        return new self(
            cast_account_id: (int) $request->cast_account_id,
            date_transaction: $request->date_transaction,
            nominal_transaction: $cleanNominal,
            cast_account_destination_id: $request->cast_account_destination_id ? (int) $request->cast_account_destination_id : null,
            description: $request->description,
            status: $request->status ?? 'enter',
            kdp: $request->kdp,
            job_name: $request->job_name,
            no_invoice: $request->no_invoice,
            account_id: $request->account_id ? (int) $request->account_id : null,
            currency_code: $currencyCode
        );
    }

    public function toArray(): array
    {
        return [
            'cast_account_id' => $this->cast_account_id,
            'date_transaction' => $this->date_transaction,
            'nominal_transaction' => $this->nominal_transaction,
            'cast_account_destination_id' => $this->cast_account_destination_id,
            'description' => $this->description,
            'status' => $this->status,
            'kdp' => $this->kdp,
            'job_name' => $this->job_name,
            'no_invoice' => $this->no_invoice,
            'account_id' => $this->account_id,
            'currency_code' => $this->currency_code,
        ];
    }
}
