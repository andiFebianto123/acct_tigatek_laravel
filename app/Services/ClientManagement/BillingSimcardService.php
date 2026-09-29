<?php

namespace App\Services\ClientManagement;

use App\Models\BillingSimcard;
use App\Models\BillingNotification;
use App\Imports\BillingSimcardImport;
use App\DTOs\ClientManagement\BillingSimcardData;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

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
    public function updateBillingSimcard(int $id, BillingSimcardData $data): BillingSimcard
    {
        return DB::transaction(function () use ($id, $data) {
            $simcard = BillingSimcard::findOrFail($id);
            $simcard->update($data->toArray());
            return $simcard;
        });
    }

    /**
     * Soft delete an existing BillingSimcard and its associated notifications.
     */
    public function deleteBillingSimcard(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $simcard = BillingSimcard::findOrFail($id);

            // Clean up associated billing notifications
            BillingNotification::where('billable_type', BillingSimcard::class)
                ->where('billable_id', $id)
                ->delete();

            return (bool) $simcard->delete();
        });
    }

    /**
     * Restore a soft-deleted BillingSimcard.
     */
    public function restoreBillingSimcard(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $simcard = BillingSimcard::withTrashed()->findOrFail($id);
            return (bool) $simcard->restore();
        });
    }
}
