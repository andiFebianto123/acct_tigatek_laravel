<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class BastData
{
    public function __construct(
        public readonly ?int $company_id,
        public readonly ?int $client_po_id,
        public readonly ?string $referenceable_type,
        public readonly ?int $referenceable_id,
        public readonly ?int $client_id,
        public readonly ?string $pic,
        public readonly ?string $phone,
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
        $company_id = $request->input('company_id') ? (int) $request->input('company_id') : null;

        $reference_type = $request->input('reference_type');
        $referenceable_type = null;
        $referenceable_id = null;
        $client_po_id = null;

        if ($reference_type === 'client_po') {
            $referenceable_type = \App\Models\ClientPo::class;
            $referenceable_id = $request->input('client_po_id') ? (int) $request->input('client_po_id') : null;
            $client_po_id = $referenceable_id;
        } else if ($reference_type === 'proforma_invoice') {
            $referenceable_type = \App\Models\ProformaInvoiceClient::class;
            $referenceable_id = $request->input('proforma_invoice_client_id') ? (int) $request->input('proforma_invoice_client_id') : null;
        }

        return new self(
            company_id: $company_id,
            client_po_id: $client_po_id,
            referenceable_type: $referenceable_type,
            referenceable_id: $referenceable_id,
            client_id: $request->input('client_id') ? (int) $request->input('client_id') : null,
            pic: $request->input('pic'),
            phone: $request->input('phone'),
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
            'referenceable_type' => $this->referenceable_type,
            'referenceable_id' => $this->referenceable_id,
            'client_id' => $this->client_id,
            'pic' => $this->pic,
            'phone' => $this->phone,
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
