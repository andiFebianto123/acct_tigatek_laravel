<?php

namespace App\Services\DeviceStock;

use App\Models\DeviceStock;
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
            return DeviceStock::create($data->toArray());
        });
    }

    /**
     * Update an existing DeviceStock.
     */
    public function updateStock(int $id, DeviceStockData $data): DeviceStock
    {
        return DB::transaction(function () use ($id, $data) {
            $stock = DeviceStock::findOrFail($id);
            $stock->update($data->toArray());
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
