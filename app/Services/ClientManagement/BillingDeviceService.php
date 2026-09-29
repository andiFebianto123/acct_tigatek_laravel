<?php

namespace App\Services\ClientManagement;

use App\Models\BillingDevice;
use App\Models\BillingNotification;
use App\Imports\BillingDeviceImport;
use App\DTOs\ClientManagement\BillingDeviceData;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BillingDeviceService
{
    /**
     * Import billing devices from an uploaded file.
     */
    public function importBillingDevices(UploadedFile $file, ?int $companyId)
    {
        // TODO(security): File validation has been performed in request layer
        Excel::import(new BillingDeviceImport($companyId), $file);
    }

    /**
     * Update an existing BillingDevice.
     */
    public function updateBillingDevice(int $id, BillingDeviceData $data): BillingDevice
    {
        return DB::transaction(function () use ($id, $data) {
            $device = BillingDevice::findOrFail($id);
            $device->update($data->toArray());
            return $device;
        });
    }

    /**
     * Soft delete an existing BillingDevice and its associated notifications.
     */
    public function deleteBillingDevice(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $device = BillingDevice::findOrFail($id);

            // Clean up associated billing notifications
            BillingNotification::where('billable_type', BillingDevice::class)
                ->where('billable_id', $id)
                ->delete();

            return (bool) $device->delete();
        });
    }

    /**
     * Restore a soft-deleted BillingDevice.
     */
    public function restoreBillingDevice(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $device = BillingDevice::withTrashed()->findOrFail($id);
            return (bool) $device->restore();
        });
    }
}
