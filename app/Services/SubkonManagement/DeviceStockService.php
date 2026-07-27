<?php

namespace App\Services\SubkonManagement;

use App\Models\DeviceStock;
use App\Models\DeviceStockHistory;
use App\Models\DeviceStockMutation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeviceStockService
{
    /**
     * Record incoming stock from Purchase Order (Supplier) or manual addition.
     *
     * @param DeviceStock $masterStock
     * @param int $qty
     * @param float $buyPrice
     * @param string $currencyCode
     * @param float $exchangeRate
     * @param Model|null $reference (e.g. PurchaseOrder)
     * @return DeviceStockHistory
     */
    public function recordIncomingStock(
        DeviceStock $masterStock,
        int $qty,
        float $buyPrice,
        string $currencyCode = 'IDR',
        float $exchangeRate = 1.0,
        ?Model $reference = null
    ): DeviceStockHistory {
        return DB::transaction(function () use ($masterStock, $qty, $buyPrice, $currencyCode, $exchangeRate, $reference) {
            $buyPriceBase = $currencyCode === 'USD' ? ($buyPrice * $exchangeRate) : $buyPrice;

            // 1. Update Master Device Stock (Current State)
            $masterStock->qty = ($masterStock->qty ?? 0) + $qty;
            $masterStock->buy_price = $buyPrice;
            $masterStock->currency_code = $currencyCode;
            $masterStock->exchange_rate = $exchangeRate;
            $masterStock->buy_price_base = $buyPriceBase;
            $masterStock->save();

            // 2. Update or Create Layer on Device Stock Histories (Grouped by device_stock_id & buy_price_base)
            $historyLayer = DeviceStockHistory::firstOrNew([
                'device_stock_id' => $masterStock->id,
                'buy_price_base'  => $buyPriceBase,
            ]);

            $qtyBeforeHistory = $historyLayer->qty ?? 0;
            
            if (!$historyLayer->exists) {
                $historyLayer->currency_code = $currencyCode;
                $historyLayer->buy_price = $buyPrice;
                $historyLayer->exchange_rate = $exchangeRate;
                $historyLayer->qty = $qty;
            } else {
                $historyLayer->qty = $qtyBeforeHistory + $qty;
            }

            $historyLayer->save();

            // 3. Create Audit Mutation Log
            DeviceStockMutation::create([
                'device_stock_id'         => $masterStock->id,
                'device_stock_history_id' => $historyLayer->id,
                'reference_type'          => $reference ? get_class($reference) : null,
                'reference_id'            => $reference ? $reference->getKey() : null,
                'type'                    => 'IN',
                'qty_change'              => $qty,
                'qty_before'              => $qtyBeforeHistory,
                'qty_after'               => $historyLayer->qty,
            ]);

            return $historyLayer;
        });
    }

    /**
     * Revert incoming stock when a Purchase Order (Supplier) is deleted or updated.
     *
     * @param Model $reference
     * @throws \Exception If any stock from this PO has already been consumed by an Invoice.
     */
    public function revertIncomingStock(Model $reference): void
    {
        DB::transaction(function () use ($reference) {
            $mutations = DeviceStockMutation::where('reference_type', get_class($reference))
                ->where('reference_id', $reference->getKey())
                ->where('type', 'IN')
                ->get();

            foreach ($mutations as $mutation) {
                $masterStock = DeviceStock::find($mutation->device_stock_id);
                $historyLayer = DeviceStockHistory::find($mutation->device_stock_history_id);
                $qtyToRevert = $mutation->qty_change;

                // Proteksi: Cek apakah stok di layer masih cukup untuk di-revert (belum terpakai di Invoice)
                if ($historyLayer && $historyLayer->qty < $qtyToRevert) {
                    throw new \Exception(trans('backpack::crud.po.error.cannot_delete_stock_used', ['name' => $masterStock?->name]) ?: "Penghapusan gagal: Stok barang '{$masterStock?->name}' dari PO ini sudah terpakai pada transaksi Invoice/Penjualan.");
                }

                // 1. Rollback Qty pada Layer History
                if ($historyLayer) {
                    $historyLayer->qty = max(0, $historyLayer->qty - $qtyToRevert);
                    $historyLayer->save();
                }

                // 2. Rollback Qty pada Master Device Stock dan Sinkronkan Ulang Harga Beli Master
                if ($masterStock) {
                    $masterStock->qty = max(0, $masterStock->qty - $qtyToRevert);
                    $this->syncMasterPriceFromLatestLayer($masterStock);
                }

                // 3. Hapus record mutasi IN terkait
                $mutation->delete();
            }
        });
    }

    /**
     * Resynchronize master stock buy price attributes from the latest active layer.
     */
    private function syncMasterPriceFromLatestLayer(DeviceStock $masterStock): void
    {
        // Cari layer terbaru yang masih memiliki stok (> 0)
        $latestLayer = DeviceStockHistory::where('device_stock_id', $masterStock->id)
            ->where('qty', '>', 0)
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestLayer) {
            // Jika tidak ada layer dengan stok > 0, ambil layer paling akhir secara historis
            $latestLayer = DeviceStockHistory::where('device_stock_id', $masterStock->id)
                ->orderBy('id', 'desc')
                ->first();
        }

        if ($latestLayer) {
            $masterStock->buy_price = $latestLayer->buy_price;
            $masterStock->currency_code = $latestLayer->currency_code;
            $masterStock->exchange_rate = $latestLayer->exchange_rate;
            $masterStock->buy_price_base = $latestLayer->buy_price_base;
        } else {
            // Jika seluruh layer terhapus / tidak ada layer sama sekali, reset harga beli ke 0
            $masterStock->buy_price = 0;
            $masterStock->buy_price_base = 0;
            $masterStock->currency_code = 'IDR';
            $masterStock->exchange_rate = 1.0;
        }

        $masterStock->save();
    }

    /**
     * Post stock for a Supplier Purchase Order.
     *
     * @param \App\Models\PurchaseOrder $po
     * @return void
     * @throws \Exception
     */
    public function processSupplierPoItems(\App\Models\PurchaseOrder $po): void
    {
        DB::transaction(function () use ($po) {
            if ($po->po_type !== 'supplier') {
                throw new \Exception(trans('backpack::crud.po.error.only_supplier_po') ?: "Hanya Purchase Order bertipe Supplier yang dapat memposting stok.");
            }

            if ($po->is_stock_posted) {
                throw new \Exception(trans('backpack::crud.po.error.stock_already_posted') ?: "Stok untuk Purchase Order ini sudah pernah diposting sebelumnya.");
            }

            $currencyCode = $po->currency_code ?? 'IDR';
            $exchangeRate = (float) ($po->exchange_rate ?? 1.0);

            $details = $po->purchase_order_details;
            if ($details->isEmpty()) {
                throw new \Exception(trans('backpack::crud.po.error.empty_details') ?: "Purchase Order tidak memiliki item barang.");
            }

            $hasValidStockItem = false;
            foreach ($details as $poItem) {
                if ($poItem->reference_id && $poItem->qty > 0) {
                    $masterStock = DeviceStock::find($poItem->reference_id);
                    if ($masterStock) {
                        $hasValidStockItem = true;
                        $this->recordIncomingStock(
                            masterStock: $masterStock,
                            qty: $poItem->qty,
                            buyPrice: (float) $poItem->price,
                            currencyCode: $currencyCode,
                            exchangeRate: $exchangeRate,
                            reference: $po
                        );
                    }
                }
            }

            if (!$hasValidStockItem) {
                throw new \Exception(trans('backpack::crud.po.error.no_valid_stock_items') ?: "Tidak ada item barang device stock yang dapat diposting pada PO ini.");
            }

            $po->is_stock_posted = true;
            $po->stock_posted_at = now();
            $po->save();
        });
    }
}
