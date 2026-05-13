<?php

namespace App\DTOs\CastAccount;

use Illuminate\Http\Request;

class TransactionSaveData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $cast_account_id,
        public readonly string $date_transaction,
        public readonly string $status,
        public readonly float $nominal_transaction,
        public readonly ?string $kdp,
        public readonly ?string $job_name,
        public readonly ?string $no_invoice,
        public readonly ?int $account_id,
        public readonly ?string $description
    ) {}

    public static function fromRequest(Request $request): self
    {
        $cleanNominal = fn($val) => (float) str_replace('.', '', $val ?? '0');

        return new self(
            id: $request->id ? (int) $request->id : null,
            cast_account_id: (int) ($request->cast_account_id ?? $request->_id),
            date_transaction: $request->date_transaction,
            status: $request->status,
            nominal_transaction: $cleanNominal($request->nominal_transaction),
            kdp: $request->kdp,
            job_name: $request->job_name,
            no_invoice: $request->no_invoice,
            account_id: $request->account_id ? (int) $request->account_id : null,
            description: $request->description
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'cast_account_id' => $this->cast_account_id,
            'date_transaction' => $this->date_transaction,
            'status' => $this->status,
            'nominal_transaction' => $this->nominal_transaction,
            'kdp' => $this->kdp,
            'job_name' => $this->job_name,
            'no_invoice' => $this->no_invoice,
            'account_id' => $this->account_id,
            'description' => $this->description,
        ], fn($v) => $v !== null);
    }
}
