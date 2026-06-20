<?php

namespace App\Services\ClientManagement;

use App\Models\TransactionHistory;
use App\DTOs\ClientManagement\TransactionHistoryData;
use App\Imports\TransactionHistoryImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TransactionHistoryService
{
    /**
     * Import transaction histories from an uploaded file.
     */
    public function importTransactionHistories(UploadedFile $file, ?int $companyId)
    {
        Excel::import(new TransactionHistoryImport($companyId), $file);
    }

    /**
     * Create a new TransactionHistory.
     */
    public function createTransactionHistory(TransactionHistoryData $data): TransactionHistory
    {
        return DB::transaction(function () use ($data) {
            return TransactionHistory::create($data->toArray());
        });
    }

    /**
     * Update an existing TransactionHistory.
     */
    public function updateTransactionHistory(int $id, TransactionHistoryData $data): TransactionHistory
    {
        return DB::transaction(function () use ($id, $data) {
            $history = TransactionHistory::findOrFail($id);
            $history->update($data->toArray());
            return $history;
        });
    }

    /**
     * Delete a TransactionHistory.
     */
    public function deleteTransactionHistory(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $history = TransactionHistory::findOrFail($id);
            return (bool) $history->delete();
        });
    }
}
