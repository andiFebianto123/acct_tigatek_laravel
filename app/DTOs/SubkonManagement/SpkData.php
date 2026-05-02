<?php

namespace App\DTOs\SubkonManagement;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SpkData
{
    public function __construct(
        public ?int $subkon_id,
        public ?string $no_spk,
        public ?string $date_spk,
        public ?string $work_code,
        public ?string $job_name,
        public ?string $job_description,
        public ?float $job_value,
        public ?float $tax_ppn,
        public ?float $total_value_with_tax,
        public ?string $due_date,
        public ?string $status,
        public UploadedFile|string|null $document_path,
        public ?string $additional_info,
        public ?int $company_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            subkon_id: $request->input('subkon_id'),
            no_spk: $request->input('no_spk'),
            date_spk: $request->input('date_spk'),
            work_code: $request->input('work_code'),
            job_name: $request->input('job_name'),
            job_description: $request->input('job_description'),
            job_value: (float) $request->input('job_value'),
            tax_ppn: (float) $request->input('tax_ppn'),
            total_value_with_tax: (float) $request->input('total_value_with_tax'),
            due_date: $request->input('due_date'),
            status: $request->input('status'),
            document_path: $request->file('document_path') ?? $request->input('document_path'),
            additional_info: $request->input('additional_info'),
            company_id: backpack_user()->hasRole('Super Admin') ? $request->input('company_id') : backpack_user()->company_id,
        );
    }

    public function toArray(): array
    {
        $data = [
            'subkon_id' => $this->subkon_id,
            'no_spk' => $this->no_spk,
            'date_spk' => $this->date_spk,
            'work_code' => $this->work_code,
            'job_name' => $this->job_name,
            'job_description' => $this->job_description,
            'job_value' => $this->job_value,
            'tax_ppn' => $this->tax_ppn,
            'total_value_with_tax' => $this->total_value_with_tax,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'additional_info' => $this->additional_info,
            'company_id' => $this->company_id,
        ];

        if (is_string($this->document_path)) {
            $data['document_path'] = $this->document_path;
        }

        return $data;
    }
}
