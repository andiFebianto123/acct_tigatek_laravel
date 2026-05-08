<?php

namespace App\DTOs\Invoice;

use Illuminate\Http\Request;

class InvoiceClientFilterData
{
    public function __construct(
        public readonly mixed $columns = null,
        public readonly ?string $invoice_date = null,
        public readonly ?string $po_date = null,
        public readonly ?string $send_invoice_normal = null,
        public readonly ?string $send_invoice_revision = null,
        public readonly ?string $filter_paid_status = null,
        public readonly ?string $filter_year = null,
        public readonly ?int $company_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            columns: $request->columns ?? $request->search,
            invoice_date: $request->invoice_date,
            po_date: $request->po_date,
            send_invoice_normal: $request->send_invoice_normal,
            send_invoice_revision: $request->send_invoice_revision,
            filter_paid_status: $request->filter_paid_status,
            filter_year: $request->filter_year,
            company_id: (int) $request->company_id,
        );
    }
}
