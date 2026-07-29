<?php

namespace App\Services\ClientManagement;

use App\DTOs\ClientManagement\DeliveryNoteData;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteDetail;
use App\Models\DeviceStock;
use App\Models\DeviceStockHistory;
use App\Models\DeviceStockMutation;
use Illuminate\Support\Facades\DB;

class DeliveryNoteService
{
    /**
     * Create a new Delivery Note dan pemicu pemotongan stok FIFO langsung.
     */
    public function createDeliveryNote(DeliveryNoteData $data): DeliveryNote
    {
        return DB::transaction(function () use ($data) {
            // Validasi kecukupan stok dulu
            $this->validateStockSufficiency($data->delivery_note_details);

            $attributes = $data->toArray();
            unset($attributes['delivery_note_details']);

            $deliveryNote = DeliveryNote::create($attributes);

            $this->saveDetails($deliveryNote, $data->delivery_note_details);

            // Memotong stok FIFO gudang untuk item persediaan
            $this->consumeFifoStock($deliveryNote);

            return $deliveryNote;
        });
    }

    /**
     * Cek apakah Surat Jalan sudah terhubung ke Invoice Client.
     */
    public function isLinkedToInvoice(DeliveryNote $deliveryNote): bool
    {
        if (!empty($deliveryNote->invoice_client_id)) {
            return true;
        }
        return \App\Models\InvoiceClient::where('delivery_note_id', $deliveryNote->id)->exists();
    }

    /**
     * Update an existing Delivery Note.
     */
    public function updateDeliveryNote(int $id, DeliveryNoteData $data): DeliveryNote
    {
        return DB::transaction(function () use ($id, $data) {
            $deliveryNote = DeliveryNote::findOrFail($id);

            if ($this->isLinkedToInvoice($deliveryNote)) {
                throw new \Exception(trans('backpack::crud.delivery_note.cannot_edit_billed'));
            }

            // Revert stok FIFO sebelumnya sebelum memproses ulang
            $this->revertFifoStock($deliveryNote);

            // Validasi stok baru
            $this->validateStockSufficiency($data->delivery_note_details);

            $attributes = $data->toArray();
            unset($attributes['delivery_note_details']);

            $deliveryNote->update($attributes);

            $this->saveDetails($deliveryNote, $data->delivery_note_details);

            // Eksekusi ulang stok FIFO untuk item baru
            $this->consumeFifoStock($deliveryNote);

            return $deliveryNote;
        });
    }

    /**
     * Delete a Delivery Note dan kembalikan stok FIFO.
     */
    public function deleteDeliveryNote(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $deliveryNote = DeliveryNote::findOrFail($id);

            if ($this->isLinkedToInvoice($deliveryNote)) {
                throw new \Exception(trans('backpack::crud.delivery_note.cannot_delete_billed'));
            }

            $this->revertFifoStock($deliveryNote);

            return (bool) $deliveryNote->delete();
        });
    }

    /**
     * Simpan baris rincian item ke delivery_note_details.
     */
    private function saveDetails(DeliveryNote $deliveryNote, array $details): void
    {
        DeliveryNoteDetail::where('delivery_note_id', $deliveryNote->id)->delete();

        foreach ($details as $item) {
            $deviceStockId = !empty($item['device_stock_id']) ? (int) $item['device_stock_id'] : null;
            $stock = $deviceStockId ? DeviceStock::find($deviceStockId) : null;

            $description = $item['description'] ?? null;
            if (!$description && $stock) {
                $description = $stock->name;
            }

            DeliveryNoteDetail::create([
                'delivery_note_id' => $deliveryNote->id,
                'device_stock_id'  => $deviceStockId,
                'description'      => $description,
                'qty'              => max(1, (int) ($item['qty'] ?? 1)),
                'cogs_amount'      => 0,
                'cogs_amount_base' => 0,
            ]);
        }
    }

    /**
     * Validasi ketersediaan stok fisik barang master.
     */
    public function validateStockSufficiency(array $details): void
    {
        $totals = [];
        foreach ($details as $d) {
            if (!empty($d['device_stock_id'])) {
                $stockId = (int) $d['device_stock_id'];
                $qty = max(1, (int) ($d['qty'] ?? 1));
                $totals[$stockId] = ($totals[$stockId] ?? 0) + $qty;
            }
        }

        foreach ($totals as $stockId => $needed) {
            $stock = DeviceStock::find($stockId);
            if ($stock) {
                $available = (int) $stock->qty;
                if ($available < $needed) {
                    throw new \Exception(trans('backpack::crud.delivery_note.stock_insufficient', [
                        'name'      => $stock->name,
                        'available' => $available,
                        'needed'    => $needed,
                    ]));
                }
            }
        }
    }

    /**
     * Konsumsi stok device secara FIFO berdasarkan detail Surat Jalan.
     */
    public function consumeFifoStock(DeliveryNote $deliveryNote): void
    {
        $details = DeliveryNoteDetail::where('delivery_note_id', $deliveryNote->id)
            ->whereNotNull('device_stock_id')
            ->get();

        foreach ($details as $detail) {
            $this->consumeDeviceStockFifo($deliveryNote, $detail);
        }
    }

    /**
     * Proses pemotongan stok FIFO untuk 1 baris detail Surat Jalan.
     */
    private function consumeDeviceStockFifo(DeliveryNote $deliveryNote, DeliveryNoteDetail $detail): void
    {
        $masterStock = DeviceStock::find($detail->device_stock_id);
        if (!$masterStock) {
            return;
        }

        $qtyNeeded = (int) $detail->qty;
        if ($qtyNeeded <= 0) {
            return;
        }

        // Ambil layers FIFO: qty > 0, ORDER BY id ASC (First In First Out)
        $layers = DeviceStockHistory::where('device_stock_id', $masterStock->id)
            ->where('qty', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        $totalCogsBase = 0.0;
        $qtyRemaining  = $qtyNeeded;

        foreach ($layers as $layer) {
            if ($qtyRemaining <= 0) break;

            $qtyFromLayer      = min($layer->qty, $qtyRemaining);
            $cogsBaseFromLayer = $qtyFromLayer * (float) $layer->buy_price_base;

            $totalCogsBase += $cogsBaseFromLayer;

            $qtyBefore  = $layer->qty;
            $layer->qty -= $qtyFromLayer;
            $layer->save();

            // Catat mutasi OUT per layer ber-referensi ke DeliveryNote
            DeviceStockMutation::create([
                'device_stock_id'         => $masterStock->id,
                'device_stock_history_id' => $layer->id,
                'reference_type'          => DeliveryNote::class,
                'reference_id'            => $deliveryNote->id,
                'type'                    => 'OUT',
                'qty_before'              => $qtyBefore,
                'qty_change'              => -$qtyFromLayer,
                'qty_after'               => $layer->qty,
                'buy_price_base'          => $layer->buy_price_base,
                'note'                    => 'FIFO OUT - Surat Jalan #' . $deliveryNote->number,
            ]);

            $qtyRemaining -= $qtyFromLayer;
        }

        // Update master stock qty
        $masterStock->qty = max(0, $masterStock->qty - $qtyNeeded);
        $masterStock->save();

        // Simpan COGS ke detail Surat Jalan
        $detail->cogs_amount_base = round($totalCogsBase, 4);
        $detail->cogs_amount      = round($totalCogsBase, 4);
        $detail->save();
    }

    /**
     * Kembalikan (revert) stok FIFO dari seluruh mutasi OUT Surat Jalan ini.
     */
    public function revertFifoStock(DeliveryNote $deliveryNote): void
    {
        $mutations = DeviceStockMutation::where('reference_type', DeliveryNote::class)
            ->where('reference_id', $deliveryNote->id)
            ->where('type', 'OUT')
            ->get();

        foreach ($mutations as $mutation) {
            $layer  = DeviceStockHistory::find($mutation->device_stock_history_id);
            $master = DeviceStock::find($mutation->device_stock_id);

            $qtyToReturn = abs($mutation->qty_change);

            if ($layer) {
                $layer->qty += $qtyToReturn;
                $layer->save();
            }

            if ($master) {
                $master->qty += $qtyToReturn;
                $master->save();
            }

            $mutation->delete();
        }

        // Reset COGS pada detail
        DeliveryNoteDetail::where('delivery_note_id', $deliveryNote->id)
            ->whereNotNull('device_stock_id')
            ->update(['cogs_amount' => 0, 'cogs_amount_base' => 0]);
    }

    /**
     * Get UI events for the JSON response.
     */
    public function getUIEvents(DeliveryNote $item, string $actionType = 'create'): array
    {
        $suffix = $actionType === 'create' ? 'create_success' : 'updated_success';

        return [
            'crudTable-filter_delivery_note_plugin_load' => true,
            "crudTable-delivery_note_{$suffix}" => true,
        ];
    }
}
