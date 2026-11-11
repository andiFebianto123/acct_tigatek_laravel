<?php

namespace App\Services\DeviceStock;

use App\Models\DeviceStock;
use App\Models\DeviceStockHistory;
use App\Models\DeviceStockMutation;
use App\Models\Setting;
use App\DTOs\DeviceStock\DeviceStockData;
use Illuminate\Support\Facades\DB;

class DeviceStockService
{
    /**
     * Create a new DeviceStock.
     */
    public function createStock(DeviceStockData $data): DeviceStock
    {
        return DB::transaction(function () use ($data) {
            $payload = $data->toArray();

            $currencyCode = $data->currency_code ?? 'IDR';
            $usdRate = Setting::first()?->usd_rate ?? 16000;
            $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

            $payload['currency_code'] = $currencyCode;
            $payload['exchange_rate'] = $exchangeRate;
            $payload['sell_price_base'] = $data->sell_price * $exchangeRate;
            $payload['buy_price_base'] = $data->buy_price * $exchangeRate;

            $stock = DeviceStock::create($payload);
            $this->syncInitialStockLayer($stock);
            return $stock;
        });
    }

    /**
     * Update an existing DeviceStock.
     */
    public function updateStock(int $id, DeviceStockData $data): DeviceStock
    {
        return DB::transaction(function () use ($id, $data) {
            $stock = DeviceStock::findOrFail($id);
            $payload = $data->toArray();

            $currencyCode = $data->currency_code ?? 'IDR';
            $usdRate = Setting::first()?->usd_rate ?? 16000;
            $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

            $payload['currency_code'] = $currencyCode;
            $payload['exchange_rate'] = $exchangeRate;
            $payload['sell_price_base'] = $data->sell_price * $exchangeRate;
            $payload['buy_price_base'] = $data->buy_price * $exchangeRate;

            $stock->update($payload);
            $this->syncInitialStockLayer($stock);
            return $stock;
        });
    }

    /**
     * Inisialisasi layer modal FIFO saldo awal jika barang dibuat/diupdate dengan stok awal (tanpa lewat PO).
     */
    private function syncInitialStockLayer(DeviceStock $stock): void
    {
        $qty = (int) ($stock->qty ?? 0);
        $buyPriceBase = (float) ($stock->buy_price_base ?? 0);

        if ($qty > 0 && $buyPriceBase > 0) {
            $hasHistories = DeviceStockHistory::where('device_stock_id', $stock->id)->exists();
            if (!$hasHistories) {
                $historyLayer = DeviceStockHistory::create([
                    'device_stock_id' => $stock->id,
                    'qty'             => $qty,
                    'currency_code'   => $stock->currency_code ?? 'IDR',
                    'buy_price'       => (float) ($stock->buy_price ?? 0),
                    'exchange_rate'   => (float) ($stock->exchange_rate ?? 1.0),
                    'buy_price_base'  => $buyPriceBase,
                ]);

                DeviceStockMutation::create([
                    'device_stock_id'         => $stock->id,
                    'device_stock_history_id' => $historyLayer->id,
                    'reference_type'          => DeviceStock::class,
                    'reference_id'            => $stock->id,
                    'type'                    => 'IN',
                    'qty_change'              => $qty,
                    'qty_before'              => 0,
                    'qty_after'               => $qty,
                    'buy_price_base'          => $buyPriceBase,
                    'note'                    => 'Saldo Awal - Master Device Stock #' . $stock->id,
                ]);
            }
        }
    }

    /**
     * Delete a DeviceStock.
     */
    public function deleteStock(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $stock = DeviceStock::findOrFail($id);
            return (bool) $stock->delete();
        });
    }
}
