<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class ClientData
{
    public function __construct(
        public readonly ?int $company_id,
        public readonly string $name,
        public readonly ?string $address,
        public readonly ?string $npwp,
        public readonly ?string $phone,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            company_id: $request->company_id ? (int) $request->company_id : null,
            name: $request->name,
            address: $request->address,
            npwp: $request->npwp,
            phone: $request->phone,
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'name' => $this->name,
            'address' => $this->address,
            'npwp' => $this->npwp,
            'phone' => $this->phone,
        ];
    }
}
