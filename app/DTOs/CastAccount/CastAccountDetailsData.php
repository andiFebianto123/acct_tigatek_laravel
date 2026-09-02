<?php

namespace App\DTOs\CastAccount;

use Illuminate\Http\Request;

class CastAccountDetailsData
{
    public function __construct(
        public readonly string $name,
        public readonly string $bank_name,
        public readonly string $no_account,
        public readonly ?string $bank_branch = null,
        public readonly ?string $swift_code = null,
        public readonly ?string $address = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->name,
            bank_name: $request->bank_name,
            no_account: $request->no_account,
            bank_branch: $request->bank_branch,
            swift_code: $request->swift_code,
            address: $request->address,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'no_account' => $this->no_account,
            'bank_branch' => $this->bank_branch,
            'swift_code' => $this->swift_code,
            'address' => $this->address,
        ];
    }
}
