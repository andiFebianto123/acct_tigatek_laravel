<?php

namespace App\DTOs\SubkonManagement;

use Illuminate\Http\Request;

class SubkonData
{
    public function __construct(
        public ?string $name,
        public ?string $address,
        public ?string $npwp,
        public ?string $phone,
        public ?string $bank_name,
        public ?string $bank_account,
        public ?string $account_holder_name,
        public ?int $company_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            address: $request->input('address'),
            npwp: $request->input('npwp'),
            phone: $request->input('phone'),
            bank_name: $request->input('bank_name'),
            bank_account: $request->input('bank_account'),
            account_holder_name: $request->input('account_holder_name'),
            company_id: $request->input('company_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'npwp' => $this->npwp,
            'phone' => $this->phone,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'account_holder_name' => $this->account_holder_name,
            'company_id' => $this->company_id,
        ];
    }
}
