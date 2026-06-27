<?php

namespace App\Services\ClientManagement;

use App\Imports\BillingDeviceImport;
use App\DTOs\ClientManagement\BillingDeviceData;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

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
    public function updateBillingDevice(int $id, BillingDeviceData $data): \App\Models\BillingDevice
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $device = \App\Models\BillingDevice::findOrFail($id);
            $device->update($data->toArray());
            return $device;
        });
    }
}
