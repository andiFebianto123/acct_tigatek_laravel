<?php

namespace App\DTOs\Invoice;

use Illuminate\Http\Request;

class ProformaInvoiceClientSaveData
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
        public readonly array $proforma_invoice_client_details,
        public readonly ?int $company_id = null,
        public readonly mixed $invoice_document = null,
        public readonly mixed $document_imei_iccid = null,
        public readonly ?int $account_source_id = null,
        public readonly ?string $note = null,
        public readonly ?int $client_id = null,
        public readonly ?string $type_device = null,
        public readonly ?string $term = null,
        public readonly ?string $currency_code = 'IDR',
        public readonly ?string $pic = null,
        public readonly ?int $client_quotation_id = null,
        public readonly ?string $category = 'rutin',
        public readonly ?string $status = 'Unpaid',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currencyCode = $request->input('currency_code', 'IDR');
        $cleanNominal = function ($val) use ($currencyCode) {
            if (is_numeric($val)) return (float) $val;
            $str = (string) ($val ?? '');
            if ($currencyCode === 'USD') {
                return (float) str_replace(',', '', $str);
            }
            return (float) str_replace('.', '', $str);
        };

        $details = $request->proforma_invoice_client_details ?? $request->proforma_invoice_client_details_edit ?? [];
        if (is_string($details)) {
            $details = json_decode($details, true) ?? [];
        }

        return new self(
            invoice_number: $request->invoice_number,
            description: $request->description,
            invoice_date: $request->invoice_date,
            client_po_id: $request->client_po_id ? (int) $request->client_po_id : null,
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
            proforma_invoice_client_details: $details,
            company_id: $request->company_id ? (int) $request->company_id : null,
            invoice_document: $request->file('invoice_document'),
            document_imei_iccid: $request->file('document_imei_iccid'),
            account_source_id: $request->account_source_id ? (int) $request->account_source_id : null,
            note: $request->note,
            client_id: $request->client_id ? (int) $request->client_id : null,
            type_device: $request->type_device,
            term: $request->term,
            currency_code: $request->input('currency_code', 'IDR'),
            pic: $request->pic,
            client_quotation_id: $request->client_quotation_id ? (int) $request->client_quotation_id : null,
            category: $request->input('category', 'rutin'),
            status: $request->input('status', 'Unpaid') ?: 'Unpaid',
        );
    }
}
