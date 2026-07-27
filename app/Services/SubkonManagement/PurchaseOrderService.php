<?php

namespace App\Services\SubkonManagement;

use App\Models\PurchaseOrder;
use App\Models\Setting;
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

            // Multi-Currency & Exchange Rate Calculations
            $currencyCode = $data->currency_code ?? 'IDR';
            $usdRate = Setting::first()?->usd_rate ?? 16000;
            $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

            $payload['currency_code'] = $currencyCode;
            $payload['exchange_rate'] = $exchangeRate;
            $payload['job_value_base'] = $data->job_value * $exchangeRate;

            // Centralized Tax Calculation
            $payload['total_value_with_tax'] = $this->calculateTotalWithTax($data->job_value, $data->tax_ppn);
            $payload['total_value_with_tax_base'] = $payload['total_value_with_tax'] * $exchangeRate;

            // Auto generate work_code for Supplier PO
            if (($payload['po_type'] ?? null) === 'supplier') {
                if (empty($payload['work_code']) || !str_starts_with($payload['work_code'], 'WRK-')) {
                    do {
                        $uniqueCode = 'WRK-' . strtoupper(Str::random(8));
                    } while (PurchaseOrder::where('work_code', $uniqueCode)->exists());
                    $payload['work_code'] = $uniqueCode;
                }
            }

            // File Handling
            if ($data->document_path instanceof UploadedFile) {
                $filename = $this->generateCustomFilename($data->document_path);
                $data->document_path->storeAs('document_po', $filename, 'public');
                $payload['document_path'] = 'document_po/' . $filename;
            }

            $po = PurchaseOrder::create($payload);
            if (!empty($data->details)) {
                $this->saveDetails($po, $data->details);
            }
            return $po;
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

            // Multi-Currency & Exchange Rate Calculations
            $currencyCode = $data->currency_code ?? 'IDR';
            $usdRate = Setting::first()?->usd_rate ?? 16000;
            $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

            $payload['currency_code'] = $currencyCode;
            $payload['exchange_rate'] = $exchangeRate;
            $payload['job_value_base'] = $data->job_value * $exchangeRate;

            // Centralized Tax Calculation
            $payload['total_value_with_tax'] = $this->calculateTotalWithTax($data->job_value, $data->tax_ppn);
            $payload['total_value_with_tax_base'] = $payload['total_value_with_tax'] * $exchangeRate;

            // Auto generate work_code for Supplier PO
            if (($payload['po_type'] ?? null) === 'supplier') {
                if (empty($payload['work_code']) || !str_starts_with($payload['work_code'], 'WRK-')) {
                    do {
                        $uniqueCode = 'WRK-' . strtoupper(Str::random(8));
                    } while (PurchaseOrder::where('work_code', $uniqueCode)->exists());
                    $payload['work_code'] = $uniqueCode;
                }
            }

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
            $po->job_value_base = $payload['job_value_base'];
            $po->total_value_with_tax_base = $payload['total_value_with_tax_base'];
            $po->save();

            $deviceStockService = app(\App\Services\SubkonManagement\DeviceStockService::class);

            // Jika PO Supplier yang sudah diposting di-update, revert stok lama dan kembalikan status ke unposted/draft
            if ($po->po_type === 'supplier' && $po->is_stock_posted) {
                $deviceStockService->revertIncomingStock($po);
                $payload['is_stock_posted'] = false;
                $payload['stock_posted_at'] = null;
            }

            $po->fill($payload);
            $po->total_value_with_tax = $payload['total_value_with_tax'];
            $po->job_value_base = $payload['job_value_base'];
            $po->total_value_with_tax_base = $payload['total_value_with_tax_base'];
            $po->save();

            if (!empty($data->details)) {
                $this->saveDetails($po, $data->details);
            }

            return $po;
        });
    }

    /**
     * Post stock for a Supplier Purchase Order.
     */
    public function postStock(int $id): PurchaseOrder
    {
        return DB::transaction(function () use ($id) {
            $po = PurchaseOrder::findOrFail($id);
            $deviceStockService = app(\App\Services\SubkonManagement\DeviceStockService::class);

            $deviceStockService->processSupplierPoItems($po);

            return $po->fresh();
        });
    }

    private function parseItemPrice($val, string $currencyCode = 'IDR'): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }
        $str = (string) ($val ?? 0);
        if (strtoupper($currencyCode) === 'USD') {
            return (float) str_replace(',', '', $str);
        }
        return (float) str_replace('.', '', $str);
    }

    private function saveDetails(PurchaseOrder $po, array $details): void
    {
        $currencyCode = $po->currency_code ?? 'IDR';
        $exchangeRate = (float) ($po->exchange_rate ?? 1.0);

        \App\Models\PurchaseOrderDetail::where('purchase_order_id', $po->id)->delete();

        foreach ($details as $item) {
            $price = $this->parseItemPrice($item['price'] ?? 0, $currencyCode);
            $refId = !empty($item['reference_id']) ? (int) $item['reference_id'] : null;
            $name = $item['name'] ?? '';

            if ($refId) {
                $deviceStock = \App\Models\DeviceStock::find($refId);
                if ($deviceStock) {
                    $name = $deviceStock->name;
                }
            }

            if ($price > 0 || !empty($name) || $refId) {
                $poItem = new \App\Models\PurchaseOrderDetail();
                $poItem->purchase_order_id = $po->id;
                $poItem->reference_id = $refId;
                $poItem->reference_type = $refId ? \App\Models\DeviceStock::class : null;
                $poItem->name = $name;
                $poItem->qty = (int) ($item['qty'] ?? 1);
                $poItem->price = $price;
                $poItem->price_base = $price * $exchangeRate;
                $poItem->save();
            }
        }
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

            // Revert stok fisik, history layer, dan log mutasi jika PO bertipe supplier dan sudah diposting
            if ($po->po_type === 'supplier' && $po->is_stock_posted) {
                $deviceStockService = app(\App\Services\SubkonManagement\DeviceStockService::class);
                $deviceStockService->revertIncomingStock($po);
            }

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
