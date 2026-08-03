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
        public readonly ?string $term = null,
        public readonly ?int $client_id = null,
        public readonly ?string $currency_code = 'IDR',
        public readonly ?int $delivery_note_id = null,
        public readonly ?string $pic = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currencyCode = $request->input('currency_code', 'IDR');

        $cleanPercent = function ($val) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;
            $str = str_replace(',', '.', trim((string) $val));
            return (float) $str;
        };

        $cleanNominal = function ($val) use ($currencyCode) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;

            $str = trim((string) $val);
            if ($currencyCode === 'USD') {
                if (strpos($str, ',') !== false && strpos($str, '.') === false) {
                    $str = str_replace(',', '.', $str);
                } else {
                    $str = str_replace(',', '', $str);
                }
                return (float) $str;
            }

            // IDR
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
            return (float) $str;
        };

        $details = $request->invoice_client_details ?? $request->invoice_client_details_edit ?? [];
        if (is_string($details)) {
            $details = json_decode($details, true) ?? [];
        }

        $nominalExclude = $cleanNominal($request->nominal_exclude_ppn);
        $taxPpn = $cleanPercent($request->tax_ppn);
        $nominalInclude = $cleanNominal($request->nominal_include_ppn);

        if ($nominalInclude <= 0 && $nominalExclude > 0) {
            $nominalInclude = $nominalExclude + ($nominalExclude * $taxPpn / 100);
            if ($currencyCode === 'IDR') {
                $nominalInclude = round($nominalInclude);
            }
        }

        return new self(
            invoice_number: $request->invoice_number,
            description: $request->description,
            invoice_date: $request->invoice_date,
            client_po_id: $request->client_po_id ? (int) $request->client_po_id : null,
            nominal_exclude_ppn: $nominalExclude,
            nominal_include_ppn: $nominalInclude,
            tax_ppn: $taxPpn,
            pph: $cleanPercent($request->pph ?? 0),
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
            term: $request->term,
            client_id: $request->client_id ? (int) $request->client_id : null,
            currency_code: $currencyCode,
            delivery_note_id: $request->delivery_note_id ? (int) $request->delivery_note_id : null,
            pic: $request->pic,
        );
    }
}
