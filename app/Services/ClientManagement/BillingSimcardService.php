<?php

namespace App\Services\ClientManagement;

use App\Imports\BillingSimcardImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

class BillingSimcardService
{
    /**
     * Import billing SIM cards from an uploaded file.
     */
    public function importBillingSimcards(UploadedFile $file, ?int $companyId)
    {
        Excel::import(new BillingSimcardImport($companyId), $file);
    }
}
