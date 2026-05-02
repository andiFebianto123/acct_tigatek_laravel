<?php

namespace App\Services\SubkonManagement;

use App\Models\PurchaseOrder;
use App\DTOs\SubkonManagement\PurchaseOrderData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PurchaseOrderService
{
    /**
     * Store a new Purchase Order.
     */
    public function createPO(PurchaseOrderData $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $payload = $data->toArray();

            // Centralized Tax Calculation
            $payload['total_value_with_tax'] = $this->calculateTotalWithTax($data->job_value, $data->tax_ppn);

            // File Handling
            if ($data->document_path instanceof UploadedFile) {
                $filename = $this->generateCustomFilename($data->document_path);
                $data->document_path->storeAs('document_po', $filename, 'public');
                $payload['document_path'] = 'document_po/' . $filename;
            }

            return PurchaseOrder::create($payload);
        });
    }

    /**
     * Update an existing Purchase Order.
     */
    public function updatePO(int $id, PurchaseOrderData $data): PurchaseOrder
    {
        return DB::transaction(function () use ($id, $data) {
            $po = PurchaseOrder::findOrFail($id);
            $payload = $data->toArray();

            // Centralized Tax Calculation
            $payload['total_value_with_tax'] = $this->calculateTotalWithTax($data->job_value, $data->tax_ppn);

            // File Handling
            if ($data->document_path instanceof UploadedFile) {
                // Delete old file
                if ($po->document_path) {
                    Storage::disk('public')->delete($po->document_path);
                }

                // Store new file
                $filename = $this->generateCustomFilename($data->document_path);
                $data->document_path->storeAs('document_po', $filename, 'public');
                $payload['document_path'] = 'document_po/' . $filename;
            } else {
                $payload['document_path'] = $po->document_path;
            }

            $po->fill($payload);
            $po->total_value_with_tax = $payload['total_value_with_tax'];
            $po->save();

            return $po;
        });
    }

    /**
     * Calculate total value including tax.
     */
    private function calculateTotalWithTax(float $value, float $taxPercent): float
    {
        return $value + ($value * $taxPercent / 100);
    }

    /**
     * Get UI events for the JSON response (Specific to current project requirement).
     */
    public function getUIEvents(PurchaseOrder $item, string $actionType = 'create'): array
    {
        $suffix = $actionType === 'create' ? 'create_success' : 'updated_success';

        return [
            "crudTable-list_all_po_{$suffix}" => $item,
            "crudTable-list_open_{$suffix}" => $item,
            "crudTable-list_close_{$suffix}" => $item,
            'crudTable-filter-purchase_order_plugin_load' => $item,
        ];
    }
    /**
     * Delete a Purchase Order and its associated file.
     */
    public function deletePO(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $po = PurchaseOrder::findOrFail($id);

            // Hapus file fisik jika ada
            if ($po->document_path) {
                Storage::disk('public')->delete($po->document_path);
            }

            return (bool) $po->delete();
        });
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
}
