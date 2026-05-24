<?php

namespace App\Services\ClientManagement;

use App\DTOs\ClientManagement\BastData;
use App\Models\Bast;
use Illuminate\Support\Facades\DB;

class BastService
{
    /**
     * Create a new BAST.
     */
    public function createBast(BastData $data): Bast
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toArray();
            return Bast::create($attributes);
        });
    }

    /**
     * Update an existing BAST.
     */
    public function updateBast(int $id, BastData $data): Bast
    {
        return DB::transaction(function () use ($id, $data) {
            $bast = Bast::findOrFail($id);
            $attributes = $data->toArray();
            $bast->update($attributes);
            return $bast;
        });
    }

    /**
     * Delete a BAST.
     */
    public function deleteBast(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $bast = Bast::findOrFail($id);
            return (bool) $bast->delete();
        });
    }

    /**
     * Get UI events for the JSON response.
     */
    public function getUIEvents(Bast $item, string $actionType = 'create'): array
    {
        $suffix = $actionType === 'create' ? 'create_success' : 'updated_success';

        return [
            'crudTable-filter_bast_plugin_load' => true,
            "crudTable-bast_{$suffix}" => true,
        ];
    }
}
