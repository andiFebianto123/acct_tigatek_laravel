<?php

namespace App\Services\ClientManagement;

use App\DTOs\ClientManagement\ClientQuotationData;
use App\Models\ClientQuotation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientQuotationService
{
    /**
     * Create a new Quotation.
     */
    public function createQuotation(ClientQuotationData $data): ClientQuotation
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toArray();

            $attributes['job_value_include_ppn'] = $data->job_value + ($data->job_value * ($data->tax_ppn / 100));
            $attributes['price_after_year'] = 0;
            $attributes['price_total'] = 0;
            $attributes['load_general_value'] = 0;
            $attributes['profit_and_loss'] = 0;
            $attributes['profit_and_loss_final'] = 0;

            if ($data->document_path instanceof UploadedFile) {
                $attributes['document_path'] = $this->handleFileUpload($data->document_path);
            }

            return ClientQuotation::create($attributes);
        });
    }

    /**
     * Update an existing Quotation.
     */
    public function updateQuotation(int $id, ClientQuotationData $data): ClientQuotation
    {
        return DB::transaction(function () use ($id, $data) {
            $quotation = ClientQuotation::findOrFail($id);
            $attributes = $data->toArray();

            $attributes['job_value_include_ppn'] = $data->job_value + ($data->job_value * ($data->tax_ppn / 100));

            if ($data->document_path instanceof UploadedFile) {
                if ($quotation->document_path) {
                    Storage::disk('public')->delete($quotation->document_path);
                }
                $attributes['document_path'] = $this->handleFileUpload($data->document_path);
            } else {
                if ($quotation->document_path) {
                    Storage::disk('public')->delete($quotation->document_path);
                }
            }

            $quotation->update($attributes);
            return $quotation;
        });
    }

    /**
     * Delete a Quotation.
     */
    public function deleteQuotation(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $quotation = ClientQuotation::findOrFail($id);
            if ($quotation->document_path) {
                Storage::disk('public')->delete($quotation->document_path);
            }
            return (bool) $quotation->delete();
        });
    }

    /**
     * Handle file upload.
     */
    private function handleFileUpload(UploadedFile $file): string
    {
        $filename = $this->generateCustomFilename($file);
        $path = $file->storeAs('document_quotation', $filename, 'public');
        return $path;
    }

    /**
     * Generate a custom filename based on original name and a unique suffix.
     */
    private function generateCustomFilename(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $safeName = str_replace(' ', '-', $originalName);
        $uniqueKey = Str::random(5);

        return "{$safeName}-{$uniqueKey}.{$extension}";
    }

    /**
     * Get UI events for the JSON response.
     */
    public function getUIEvents(ClientQuotation $item, string $actionType = 'create'): array
    {
        $suffix = $actionType === 'create' ? 'create_success' : 'updated_success';

        return [
            'crudTable-filter_client_quotation_plugin_load' => true,
            "crudTable-client_quotation_{$suffix}" => true,
        ];
    }
}
