<?php

namespace App\Services\SubkonManagement;

use App\Models\Subkon;
use App\DTOs\SubkonManagement\SubkonData;
use Illuminate\Support\Facades\DB;

class SubkonService
{
    /**
     * Create a new Subkon.
     */
    public function createSubkon(SubkonData $data): Subkon
    {
        return DB::transaction(function () use ($data) {
            return Subkon::create($data->toArray());
        });
    }

    /**
     * Update an existing Subkon.
     */
    public function updateSubkon(int $id, SubkonData $data): Subkon
    {
        return DB::transaction(function () use ($id, $data) {
            $subkon = Subkon::findOrFail($id);
            $subkon->update($data->toArray());
            return $subkon;
        });
    }

    /**
     * Delete a Subkon.
     */
    public function deleteSubkon(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $subkon = Subkon::findOrFail($id);
            return (bool) $subkon->delete();
        });
    }
}
