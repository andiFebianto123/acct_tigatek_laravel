<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class BastData
{
    public function __construct(
        public readonly ?int $company_id,
        public readonly ?int $client_po_id,
        public readonly ?int $client_id,
        public readonly ?string $number,
        public readonly ?string $date,
        public readonly ?string $first_party,
        public readonly ?string $first_party_address,
        public readonly ?string $address,
        public readonly ?string $description,
        public readonly int $qty,
        public readonly ?string $information,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $company_id = null;
        if (backpack_user() && backpack_user()->hasRole('Super Admin')) {
            $company_id = $request->input('company_id') ? (int) $request->input('company_id') : null;
        } else if (backpack_user()) {
            $company_id = backpack_user()->company_id ? (int) backpack_user()->company_id : null;
        }

        return new self(
            company_id: $company_id,
            client_po_id: $request->input('client_po_id') ? (int) $request->input('client_po_id') : null,
            client_id: $request->input('client_id') ? (int) $request->input('client_id') : null,
            number: $request->input('number'),
            date: $request->input('date'),
            first_party: $request->input('first_party'),
            first_party_address: $request->input('first_party_address'),
            address: $request->input('address'),
            description: $request->input('description'),
            qty: (int) ($request->input('qty') ?? 1),
            information: $request->input('information'),
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'client_po_id' => $this->client_po_id,
            'client_id' => $this->client_id,
            'number' => $this->number,
            'date' => $this->date,
            'first_party' => $this->first_party,
            'first_party_address' => $this->first_party_address,
            'address' => $this->address,
            'description' => $this->description,
            'qty' => $this->qty,
            'information' => $this->information,
        ];
    }
}
