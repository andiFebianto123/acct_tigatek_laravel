<?php

namespace App\DTOs\Invoice;

use Illuminate\Http\Request;

class InvoiceClientSaveData
{
    public function __construct(
        public readonly ?string $invoice_number,
        public readonly ?string $description,
        public readonly ?string $invoice_date,
        public readonly ?int $client_po_id,
        public readonly ?float $nominal_exclude_ppn,
        public readonly ?float $nominal_include_ppn,
        public readonly ?float $tax_ppn,
        public readonly ?float $pph,
        public readonly ?float $dpp_other,
        public readonly ?string $kdp,
        public readonly ?string $withholding_agent,
        public readonly ?string $send_invoice_normal,
        public readonly ?string $send_invoice_revision,
        public readonly ?string $address_po,
        public readonly array $invoice_client_details,
        public readonly ?int $company_id = null,
        public readonly mixed $invoice_document = null,
        public readonly ?int $account_source_id = null,
        public readonly ?string $type_device = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $cleanNominal = fn($val) => (float) str_replace('.', '', $val ?? '');

        $details = $request->invoice_client_details ?? $request->invoice_client_details_edit ?? [];
        if (is_string($details)) {
            $details = json_decode($details, true) ?? [];
        }

        return new self(
            invoice_number: $request->invoice_number,
            description: $request->description,
            invoice_date: $request->invoice_date,
            client_po_id: (int) $request->client_po_id,
            nominal_exclude_ppn: $cleanNominal($request->nominal_exclude_ppn),
            nominal_include_ppn: $cleanNominal($request->nominal_include_ppn),
            tax_ppn: (float) $request->tax_ppn,
            pph: (float) ($request->pph ?? 0),
            dpp_other: $cleanNominal($request->dpp_other),
            kdp: $request->kdp,
            withholding_agent: $request->withholding_agent,
            send_invoice_normal: $request->send_invoice_normal,
            send_invoice_revision: $request->send_invoice_revision,
            address_po: $request->address_po,
            invoice_client_details: $details,
            company_id: (int) $request->company_id,
            invoice_document: $request->file('invoice_document'),
            account_source_id: (int) $request->account_source_id,
            type_device: $request->type_device,
        );
    }
}
