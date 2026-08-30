<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class DeliveryNoteData
{
    public function __construct(
        public readonly ?int $company_id,
        public readonly ?int $client_po_id,
        public readonly ?int $invoice_client_id,
        public readonly ?int $client_id,
        public readonly ?string $pic,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $date,
        public readonly ?string $number,
        public readonly ?string $description,
        public readonly int $qty,
        public readonly ?string $information,
        public readonly ?string $reference_type,
        public readonly ?int $reference_id,
        public readonly array $delivery_note_details = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $company_id = $request->input('company_id') ? (int) $request->input('company_id') : null;

        $referenceType = $request->input('reference_type') ?: null;
        $referenceId   = $request->input('reference_id') ? (int) $request->input('reference_id') : null;

        // invoice_client_id diisi otomatis dari reference_id jika reference_type = invoice_client
        $invoiceClientId = null;
        if ($referenceType === 'invoice_client' && $referenceId) {
            $invoiceClientId = $referenceId;
        }

        $details = [];
        $rawDetails = $request->input('delivery_note_details') ?? $request->input('items') ?? [];
        if (is_string($rawDetails)) {
            $rawDetails = json_decode($rawDetails, true) ?? [];
        }
        if (is_array($rawDetails)) {
            foreach ($rawDetails as $d) {
                if (empty($d['device_stock_id']) && empty($d['description'])) {
                    continue;
                }
                $details[] = [
                    'device_stock_id' => !empty($d['device_stock_id']) ? (int) $d['device_stock_id'] : null,
                    'description'     => $d['description'] ?? null,
                    'qty'             => max(1, (int) ($d['qty'] ?? 1)),
                ];
            }
        }

        return new self(
            company_id: $company_id,
            client_po_id: $request->input('client_po_id') ? (int) $request->input('client_po_id') : null,
            invoice_client_id: $invoiceClientId,
            client_id: $request->input('client_id') ? (int) $request->input('client_id') : null,
            pic: $request->input('pic'),
            phone: $request->input('phone'),
            address: $request->input('address'),
            date: $request->input('date'),
            number: $request->input('number'),
            description: $request->input('description'),
            qty: (int) ($request->input('qty') ?? 0),
            information: $request->input('information'),
            reference_type: $referenceType,
            reference_id: $referenceId,
            delivery_note_details: $details,
        );
    }

    public function toArray(): array
    {
        return [
            'company_id'        => $this->company_id,
            'client_po_id'      => $this->client_po_id,
            'invoice_client_id' => $this->invoice_client_id,
            'client_id'         => $this->client_id,
            'pic'               => $this->pic,
            'phone'             => $this->phone,
            'address'           => $this->address,
            'date'              => $this->date,
            'number'            => $this->number,
            'description'       => $this->description,
            'qty'               => $this->qty,
            'information'       => $this->information,
            'reference_type'    => $this->reference_type,
            'reference_id'      => $this->reference_id,
        ];
    }
}
