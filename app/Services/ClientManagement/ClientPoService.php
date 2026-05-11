<?php

namespace App\Services\ClientManagement;

use App\DTOs\ClientManagement\ClientPoData;
use App\Models\ClientPo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientPoService
{
    /**
     * Create a new Client PO.
     */
    public function createClientPo(ClientPoData $data): ClientPo
    {
        return DB::transaction(function () use ($data) {
            $quotationIds = $data->quotation_ids ?? [];

            if (!$data->is_from_quotation || empty($quotationIds)) {
                // Standard single creation (Manual entry)
                $attributes = $data->toArray();

                // Handle file upload manually
                if ($data->document_path instanceof UploadedFile) {
                    $attributes['document_path'] = $this->handleFileUpload($data->document_path);
                }

                $po = ClientPo::create($attributes);
                return $po;
            }

            $lastPo = null;
            $quotations = \App\Models\ClientQuotation::whereIn('id', $quotationIds)->get();

            foreach ($quotations as $quotation) {
                $attributes = $data->toArray();

                // Populate from this specific quotation
                $attributes['client_id'] = $quotation->client_id;
                $attributes['company_id'] = $quotation->company_id;
                $attributes['job_name'] = $quotation->job_name;
                $attributes['job_value'] = $quotation->job_value;
                $attributes['rap_value'] = $quotation->rap_value;
                $attributes['tax_ppn'] = $quotation->tax_ppn;
                $attributes['work_code'] = $quotation->work_code;
                $attributes['po_number'] = $quotation->po_number ?? '-';
                $attributes['reimburse_type'] = $quotation->reimburse_type;
                $attributes['category'] = $quotation->category;
                $attributes['status'] = $quotation->status ?? 'ADA PO';
                $attributes['start_date'] = $quotation->start_date;
                $attributes['end_date'] = $quotation->end_date;
                $attributes['date_po'] = $quotation->date_po;
                $attributes['document_path'] = $quotation->document_path;

                // If user uploaded a new file in the form, use it instead of quotation's file
                if ($data->document_path instanceof UploadedFile) {
                    $attributes['document_path'] = $this->handleFileUpload($data->document_path);
                }

                // Re-calculate or ensure values are set correctly
                $attributes['job_value_include_ppn'] = $attributes['job_value'] + ($attributes['job_value'] * ($attributes['tax_ppn'] / 100));
                $attributes['price_after_year'] = 0;
                $attributes['price_total'] = 0;
                $attributes['load_general_value'] = 0;
                $attributes['profit_and_loss'] = 0;
                $attributes['profit_and_loss_final'] = 0;

                $po = ClientPo::create($attributes);
                $po->quotations()->attach($quotation->id);

                $lastPo = $po;
            }

            return $lastPo;
        });
    }

    /**
     * Update an existing Client PO.
     */
    public function updateClientPo(int $id, ClientPoData $data): ClientPo
    {
        return DB::transaction(function () use ($id, $data) {
            $clientPo = ClientPo::findOrFail($id);
            $attributes = $data->toArray();

            // Re-calculate or ensure values are set correctly
            $attributes['job_value_include_ppn'] = $data->job_value + ($data->job_value * ($data->tax_ppn / 100));

            if ($data->document_path instanceof UploadedFile) {
                if ($clientPo->document_path) {
                    Storage::disk('public')->delete($clientPo->document_path);
                }
                $attributes['document_path'] = $this->handleFileUpload($data->document_path);
            }

            $clientPo->update($attributes);
            return $clientPo;
        });
    }

    /**
     * Delete a Client PO.
     */
    public function deleteClientPo(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $clientPo = ClientPo::findOrFail($id);
            if ($clientPo->document_path) {
                Storage::disk('public')->delete($clientPo->document_path);
            }
            return (bool) $clientPo->delete();
        });
    }

    /**
     * Handle file upload.
     */
    private function handleFileUpload(UploadedFile $file): string
    {
        $filename = $this->generateCustomFilename($file);
        $path = $file->storeAs('document_client_po', $filename, 'public');
        return $path;
    }

    /**
     * Generate a custom filename based on original name and a unique suffix.
     */
    private function generateCustomFilename(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        // Ganti spasi dengan "-"
        $safeName = str_replace(' ', '-', $originalName);

        // Tambahkan 5 karakter unik di belakang
        $uniqueKey = Str::random(5);

        return "{$safeName}-{$uniqueKey}.{$extension}";
    }

    /**
     * Get UI events for the JSON response.
     */
    public function getUIEvents(ClientPo $item, string $actionType = 'create'): array
    {
        $suffix = $actionType === 'create' ? 'create_success' : 'updated_success';

        return [
            'crudTable-filter_client_po_plugin_load' => true,
            "crudTable-client_po_{$suffix}" => true,
        ];
    }
}
