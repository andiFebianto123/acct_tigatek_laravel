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

                // Auto generate work_code for Supplier PO
                if (($attributes['po_type'] ?? null) === 'supplier') {
                    if (empty($attributes['work_code']) || !str_starts_with($attributes['work_code'], 'WRK-')) {
                        do {
                            $uniqueCode = 'WRK-' . strtoupper(Str::random(8));
                        } while (ClientPo::where('work_code', $uniqueCode)->exists());
                        $attributes['work_code'] = $uniqueCode;
                    }
                }

                // Handle file upload manually
                if ($data->document_path instanceof UploadedFile) {
                    $attributes['document_path'] = $this->handleFileUpload($data->document_path);
                }

                $usdRate = \App\Models\Setting::first()->usd_rate ?? 16000;
                $currencyCode = $attributes['currency_code'] ?? 'IDR';
                $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

                $attributes['exchange_rate'] = $exchangeRate;
                $attributes['rap_value_base'] = ($attributes['rap_value'] ?? 0) * $exchangeRate;
                $attributes['job_value_base'] = ($attributes['job_value'] ?? 0) * $exchangeRate;
                $attributes['job_value_include_ppn'] = ($attributes['job_value'] ?? 0) + (($attributes['job_value'] ?? 0) * (($attributes['tax_ppn'] ?? 0) / 100));
                $attributes['job_value_include_ppn_base'] = $attributes['job_value_include_ppn'] * $exchangeRate;

                $po = ClientPo::create($attributes);
                $this->linkVoucherToClientPo($po);
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
                $this->linkVoucherToClientPo($po);

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
            $oldPoType = $clientPo->po_type;
            $oldPurchaseOrderId = $clientPo->purchase_order_id;

            $attributes = $data->toArray();

            // Auto generate work_code for Supplier PO
            if (($attributes['po_type'] ?? null) === 'supplier') {
                if (empty($attributes['work_code']) || !str_starts_with($attributes['work_code'], 'WRK-')) {
                    do {
                        $uniqueCode = 'WRK-' . strtoupper(Str::random(8));
                    } while (ClientPo::where('work_code', $uniqueCode)->exists());
                    $attributes['work_code'] = $uniqueCode;
                }
            }

            // Re-calculate or ensure values are set correctly
            $usdRate = \App\Models\Setting::first()->usd_rate ?? 16000;
            $currencyCode = $attributes['currency_code'] ?? 'IDR';
            $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

            $attributes['exchange_rate'] = $exchangeRate;
            $attributes['rap_value_base'] = ($attributes['rap_value'] ?? 0) * $exchangeRate;
            $attributes['job_value_base'] = ($attributes['job_value'] ?? 0) * $exchangeRate;
            $attributes['job_value_include_ppn'] = $data->job_value + ($data->job_value * ($data->tax_ppn / 100));
            $attributes['job_value_include_ppn_base'] = $attributes['job_value_include_ppn'] * $exchangeRate;

            if ($data->document_path instanceof UploadedFile) {
                if ($clientPo->document_path) {
                    Storage::disk('public')->delete($clientPo->document_path);
                }
                $attributes['document_path'] = $this->handleFileUpload($data->document_path);
            }

            // Rollback old voucher links if po_type changes or purchase_order_id changes
            if ($oldPoType === 'supplier' && (($attributes['po_type'] ?? null) !== 'supplier' || $oldPurchaseOrderId != ($attributes['purchase_order_id'] ?? null))) {
                \App\Models\Voucher::where('client_po_id', $clientPo->id)
                    ->where('reference_type', 'App\\Models\\PurchaseOrder')
                    ->update(['client_po_id' => null]);
            }

            // Check if switching to supplier when it was previously something else, and vouchers exist
            if ($oldPoType !== 'supplier' && ($attributes['po_type'] ?? null) === 'supplier') {
                $hasVoucher = \App\Models\Voucher::where('client_po_id', $clientPo->id)->exists();
                if ($hasVoucher) {
                    throw new \Exception(trans('backpack::crud.client_po.field.error_has_voucher'));
                }
            }

            $clientPo->update($attributes);
            $this->linkVoucherToClientPo($clientPo);
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

            // Rollback voucher links
            \App\Models\Voucher::where('client_po_id', $clientPo->id)
                ->where('reference_type', 'App\\Models\\PurchaseOrder')
                ->update(['client_po_id' => null]);

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

    private function linkVoucherToClientPo(ClientPo $clientPo): void
    {
        if ($clientPo->po_type === 'supplier' && !empty($clientPo->purchase_order_id)) {
            \App\Models\Voucher::where('reference_type', 'App\\Models\\PurchaseOrder')
                ->where('reference_id', $clientPo->purchase_order_id)
                ->whereNull('client_po_id')
                ->update(['client_po_id' => $clientPo->id]);
        }
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
