<?php

namespace App\DTOs\Invoice;

use Illuminate\Http\Request;

class ProformaInvoiceSaveData
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
        public readonly ?string $kdp,
        public readonly ?string $withholding_agent,
        public readonly ?string $send_invoice_normal,
        public readonly ?string $send_invoice_revision,
        public readonly ?string $address_po,
        public readonly array $proforma_invoice_details,
        public readonly ?int $company_id = null,
        public readonly mixed $invoice_document = null,
        public readonly ?int $account_source_id = null,
        public readonly ?string $note = null,
        public readonly ?string $subkon_id = null,
        public readonly ?string $term = null,
        public readonly ?string $currency_code = 'IDR',
        public readonly ?string $pic = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currency_code = $request->currency_code ?? 'IDR';
        $isUsd = strtoupper($currency_code) === 'USD';

        $cleanNominal = function ($val) use ($isUsd) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;
            $str = (string) $val;
            if ($isUsd) {
                return (float) str_replace(',', '', $str);
            }
            return (float) str_replace('.', '', $str);
        };

        $nominal_exclude_ppn = $cleanNominal($request->nominal_exclude_ppn);
        $tax_ppn = (float) ($request->tax_ppn ?? 0);

        $rawInclude = $request->nominal_include_ppn;
        if ($rawInclude === null || $rawInclude === '') {
            $calcInclude = $nominal_exclude_ppn + ($nominal_exclude_ppn * $tax_ppn / 100);
            $nominal_include_ppn = $isUsd ? round($calcInclude, 2) : round($calcInclude);
        } else {
            $nominal_include_ppn = $cleanNominal($rawInclude);
        }

        $details = $request->proforma_invoice_details ?? $request->proforma_invoice_details_edit ?? [];
        if (is_string($details)) {
            $details = json_decode($details, true) ?? [];
        }

        return new self(
            invoice_number: $request->invoice_number,
            description: $request->description,
            invoice_date: $request->invoice_date,
            client_po_id: $request->client_po_id ? (int) $request->client_po_id : null,
            nominal_exclude_ppn: $nominal_exclude_ppn,
            nominal_include_ppn: $nominal_include_ppn,
            tax_ppn: $tax_ppn,
            pph: (float) ($request->pph ?? 0),
            kdp: $request->kdp,
            withholding_agent: $request->withholding_agent,
            send_invoice_normal: $request->send_invoice_normal,
            send_invoice_revision: $request->send_invoice_revision,
            address_po: $request->address_po,
            proforma_invoice_details: $details,
            company_id: $request->company_id ? (int) $request->company_id : null,
            invoice_document: $request->file('invoice_document'),
            account_source_id: $request->account_source_id ? (int) $request->account_source_id : null,
            note: $request->note,
            subkon_id: $request->subkon_id ? (int) $request->subkon_id : null,
            term: $request->term,
            currency_code: $request->currency_code ?? 'IDR',
            pic: $request->pic,
        );
    }
}
