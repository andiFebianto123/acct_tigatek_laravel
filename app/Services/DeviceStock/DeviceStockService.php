<?php

namespace App\Services\DeviceStock;

use App\Models\DeviceStock;
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

            return DeviceStock::create($payload);
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
            return $stock;
        });
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
