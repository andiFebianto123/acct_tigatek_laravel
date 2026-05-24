<?php

namespace App\Services\ClientManagement;

use App\DTOs\ClientManagement\DeliveryNoteData;
use App\Models\DeliveryNote;
use Illuminate\Support\Facades\DB;

class DeliveryNoteService
{
    /**
     * Create a new Delivery Note.
     */
    public function createDeliveryNote(DeliveryNoteData $data): DeliveryNote
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toArray();
            return DeliveryNote::create($attributes);
        });
    }

    /**
     * Update an existing Delivery Note.
     */
    public function updateDeliveryNote(int $id, DeliveryNoteData $data): DeliveryNote
    {
        return DB::transaction(function () use ($id, $data) {
            $deliveryNote = DeliveryNote::findOrFail($id);
            $attributes = $data->toArray();
            $deliveryNote->update($attributes);
            return $deliveryNote;
        });
    }

    /**
     * Delete a Delivery Note.
     */
    public function deleteDeliveryNote(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $deliveryNote = DeliveryNote::findOrFail($id);
            return (bool) $deliveryNote->delete();
        });
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
