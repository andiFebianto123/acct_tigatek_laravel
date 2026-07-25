<?php

namespace App\DTOs\CastAccount;

use Illuminate\Http\Request;

class CastAccountSaveData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $bank_name,
        public readonly ?string $no_account,
        public readonly ?string $bank_branch,
        public readonly ?string $address,
        public readonly ?string $swift_code,
        public readonly ?int $account_id,
        public readonly float $total_saldo,
        public readonly array $informations = [],
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

        $informations = $request->informations;
        if (is_string($informations)) {
            $informations = json_decode($informations, true) ?? [];
        }

        return new self(
            id: $request->id ? (int) $request->id : null,
            name: $request->name,
            bank_name: $request->bank_name,
            no_account: $request->no_account,
            bank_branch: $request->bank_branch,
            address: $request->address,
            swift_code: $request->swift_code,
            account_id: $request->account_id ? (int) $request->account_id : null,
            total_saldo: $cleanNominal,
            informations: is_array($informations) ? $informations : [],
            currency_code: $currencyCode
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'no_account' => $this->no_account,
            'bank_branch' => $this->bank_branch,
            'address' => $this->address,
            'swift_code' => $this->swift_code,
            'account_id' => $this->account_id,
            'total_saldo' => $this->total_saldo,
            'currency_code' => $this->currency_code,
        ], fn($v) => $v !== null);
    }
}
