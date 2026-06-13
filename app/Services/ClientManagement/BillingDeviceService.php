<?php

namespace App\Services\ClientManagement;

use App\Imports\BillingDeviceImport;
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
}
