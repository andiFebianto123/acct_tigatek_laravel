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

    /**
     * Update an existing BillingSimcard.
     */
    public function updateBillingSimcard(int $id, \App\DTOs\ClientManagement\BillingSimcardData $data): \App\Models\BillingSimcard
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $simcard = \App\Models\BillingSimcard::findOrFail($id);
            $simcard->update($data->toArray());
            return $simcard;
        });
    }
}
