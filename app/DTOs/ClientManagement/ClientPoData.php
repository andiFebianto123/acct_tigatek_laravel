<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class ClientPoData
{
    public function __construct(
        public readonly ?int $company_id,
        public readonly string $work_code,
        public readonly int $client_id,
        public readonly string $reimburse_type,
        public readonly string $po_number,
        public readonly string $job_name,
        public readonly float $rap_value,
        public readonly float $job_value,
        public readonly float $tax_ppn,
        public readonly float $job_value_include_ppn,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly ?string $date_po,
        public readonly mixed $document_path,
        public readonly string $category,
        public readonly string $status,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            company_id: $request->company_id ? (int) $request->company_id : null,
            work_code: $request->work_code,
            client_id: (int) $request->client_id,
            reimburse_type: $request->reimburse_type,
            po_number: $request->po_number,
            job_name: $request->job_name,
            rap_value: (float) str_replace(',', '', $request->rap_value),
            job_value: (float) str_replace(',', '', $request->job_value),
            tax_ppn: (float) $request->tax_ppn,
            job_value_include_ppn: (float) $request->job_value_include_ppn,
            start_date: $request->start_date,
            end_date: $request->end_date,
            date_po: $request->date_po,
            document_path: $request->file('document_path') ?? $request->document_path,
            category: $request->category,
            status: $request->status,
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'work_code' => $this->work_code,
            'client_id' => $this->client_id,
            'reimburse_type' => $this->reimburse_type,
            'po_number' => $this->po_number,
            'job_name' => $this->job_name,
            'rap_value' => $this->rap_value,
            'job_value' => $this->job_value,
            'tax_ppn' => $this->tax_ppn,
            'job_value_include_ppn' => $this->job_value_include_ppn,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'date_po' => $this->date_po,
            'document_path' => $this->document_path,
            'category' => $this->category,
            'status' => $this->status,
        ];
    }
}
