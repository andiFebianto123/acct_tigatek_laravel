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
    /**
     * Create a new Delivery Note dan pemicu pemotongan stok FIFO Invoice jika terkait Invoice Client.
     */
    public function createDeliveryNote(DeliveryNoteData $data): DeliveryNote
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toArray();
            $deliveryNote = DeliveryNote::create($attributes);

            if ($deliveryNote->invoice_client_id) {
                $invoice = \App\Models\InvoiceClient::find($deliveryNote->invoice_client_id);
                if ($invoice && $invoice->type_device === \App\Models\DeviceStock::class) {
                    $invoiceService = app(\App\Services\Invoice\InvoiceClientService::class);
                    $invoiceService->consumeFifoStock($invoice);
                }
            }

            return $deliveryNote;
        });
    }

    /**
     * Update an existing Delivery Note.
     */
    public function updateDeliveryNote(int $id, DeliveryNoteData $data): DeliveryNote
    {
        return DB::transaction(function () use ($id, $data) {
            $deliveryNote = DeliveryNote::findOrFail($id);
            $oldInvoiceId = $deliveryNote->invoice_client_id;
            $attributes = $data->toArray();
            $deliveryNote->update($attributes);

            $invoiceService = app(\App\Services\Invoice\InvoiceClientService::class);

            // Revert FIFO invoice lama jika berubah atau diperbarui
            if ($oldInvoiceId) {
                $oldInvoice = \App\Models\InvoiceClient::find($oldInvoiceId);
                if ($oldInvoice && $oldInvoice->type_device === \App\Models\DeviceStock::class) {
                    $invoiceService->revertFifoStock($oldInvoice);
                }
            }

            // Execute FIFO untuk invoice baru / saat ini
            if ($deliveryNote->invoice_client_id) {
                $newInvoice = \App\Models\InvoiceClient::find($deliveryNote->invoice_client_id);
                if ($newInvoice && $newInvoice->type_device === \App\Models\DeviceStock::class) {
                    $invoiceService->consumeFifoStock($newInvoice);
                }
            }

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
            if ($deliveryNote->invoice_client_id) {
                $invoice = \App\Models\InvoiceClient::find($deliveryNote->invoice_client_id);
                if ($invoice && $invoice->type_device === \App\Models\DeviceStock::class) {
                    $invoiceService = app(\App\Services\Invoice\InvoiceClientService::class);
                    $invoiceService->revertFifoStock($invoice);
                }
            }
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
