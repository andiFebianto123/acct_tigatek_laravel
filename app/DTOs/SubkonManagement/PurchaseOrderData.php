<?php

namespace App\DTOs\SubkonManagement;

use Illuminate\Http\Request;

class PurchaseOrderData
{
    public function __construct(
        public ?string $po_number,
        public ?string $date_po,
        public ?int $subkon_id,
        public ?string $work_code,
        public ?string $job_name,
        public ?string $job_description,
        public float $job_value,
        public float $tax_ppn,
        public ?string $due_date,
        public ?string $status,
        public mixed $document_path,
        public ?string $additional_info,
        public ?int $company_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            po_number: $request->input('po_number'),
            date_po: $request->input('date_po'),
            subkon_id: (int) $request->input('subkon_id'),
            work_code: $request->input('work_code'),
            job_name: $request->input('job_name'),
            job_description: $request->input('job_description'),
            job_value: (float) $request->input('job_value', 0),
            tax_ppn: (float) $request->input('tax_ppn', 0),
            due_date: $request->input('due_date'),
            status: $request->input('status'),
            document_path: $request->hasFile('document_path') ? $request->file('document_path') : $request->input('document_path'),
            additional_info: $request->input('additional_info'),
            company_id: $request->input('company_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'po_number' => $this->po_number,
            'date_po' => $this->date_po,
            'subkon_id' => $this->subkon_id,
            'work_code' => $this->work_code,
            'job_name' => $this->job_name,
            'job_description' => $this->job_description,
            'job_value' => $this->job_value,
            'tax_ppn' => $this->tax_ppn,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'document_path' => $this->document_path,
            'additional_info' => $this->additional_info,
            'company_id' => $this->company_id,
        ];
    }
}
