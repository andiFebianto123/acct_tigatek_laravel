<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherData
{
    public function __construct(
        public readonly ?string $no_payment,
        public readonly ?int $account_id,
        public readonly ?int $account_source_id,
        public readonly ?string $type,
        public readonly ?int $subkon_id,
        public readonly ?int $client_po_id,
        public readonly ?int $company_id,
        public readonly ?int $reference_id,
        public readonly ?string $no_voucher,
        public readonly ?string $job_name,
        public readonly ?string $date_voucher,
        public readonly ?string $bill_number,
        public readonly ?string $bill_date,
        public readonly ?string $date_receipt_bill,
        public readonly ?string $payment_description,
        public readonly ?string $account_holder_name,
        public readonly ?string $no_account,
        public readonly ?string $payment_type,
        public readonly ?string $factur_status,
        public readonly ?string $no_factur,
        public readonly ?string $factur_date,
        public readonly ?string $due_date,
        public readonly ?string $payment_status,
        public readonly ?string $payment_date,
        public readonly float $bill_value,
        public readonly float $tax_ppn,
        public readonly float $pph_23,
        public readonly float $pph_4,
        public readonly float $pph_21,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            no_payment: $request->no_payment,
            account_id: $request->account_id,
            account_source_id: $request->account_source_id,
            type: $request->type,
            subkon_id: $request->subkon_id,
            client_po_id: $request->client_po_id,
            company_id: $request->company_id,
            reference_id: $request->reference_id,
            no_voucher: $request->no_voucher,
            job_name: $request->job_name,
            date_voucher: $request->date_voucher,
            bill_number: $request->bill_number,
            bill_date: $request->bill_date,
            date_receipt_bill: $request->date_receipt_bill,
            payment_description: $request->payment_description,
            account_holder_name: $request->account_holder_name,
            no_account: $request->no_account,
            payment_type: $request->payment_type,
            factur_status: $request->factur_status,
            no_factur: $request->no_factur,
            factur_date: $request->factur_date,
            due_date: $request->due_date,
            payment_status: $request->payment_status,
            payment_date: $request->payment_date,
            bill_value: (float) str_replace(',', '', $request->bill_value ?? 0),
            tax_ppn: (float) ($request->tax_ppn ?? 0),
            pph_23: (float) ($request->pph_23 ?? 0),
            pph_4: (float) ($request->pph_4 ?? 0),
            pph_21: (float) ($request->pph_21 ?? 0),
        );
    }
}
