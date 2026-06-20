<?php

namespace App\Repositories\ClientManagement;

use App\Models\TransactionHistory;
use App\DTOs\ClientManagement\TransactionHistoryFilterData;

class TransactionHistoryRepository
{
    /**
     * Get filtered query for the Transaction History list.
     */
    public function getFilteredData(TransactionHistoryFilterData $filters)
    {
        $query = TransactionHistory::query()->with(['company']);

        // Scoping based on company
        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('company_id', $filters->company_id);
        } else if (backpack_user() && !backpack_user()->hasRole('Super Admin')) {
            $query->where('company_id', backpack_user()->company_id);
        }

        // Apply DataTables search filters
        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Apply DataTables column search filters.
     */
    public function applySearchFilters($query, TransactionHistoryFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'transaction_id', 'type' => 'like'],
                3 => ['field' => 'device_id', 'type' => 'like'],
                4 => ['field' => 'msisdn', 'type' => 'like'],
                5 => ['field' => 'op_completion_time', 'type' => 'like'],
                6 => ['field' => 'operations', 'type' => 'like'],
                7 => ['field' => 'devices_upload', 'type' => 'equal'],
                8 => ['field' => 'device_prosses', 'type' => 'equal'],
                9 => ['field' => 'device_update', 'type' => 'equal'],
                10 => ['field' => 'last_update', 'type' => 'like'],
                11 => ['field' => 'status', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'transaction_id', 'type' => 'like'],
                2 => ['field' => 'device_id', 'type' => 'like'],
                3 => ['field' => 'msisdn', 'type' => 'like'],
                4 => ['field' => 'op_completion_time', 'type' => 'like'],
                5 => ['field' => 'operations', 'type' => 'like'],
                6 => ['field' => 'devices_upload', 'type' => 'equal'],
                7 => ['field' => 'device_prosses', 'type' => 'equal'],
                8 => ['field' => 'device_update', 'type' => 'equal'],
                9 => ['field' => 'last_update', 'type' => 'like'],
                10 => ['field' => 'status', 'type' => 'like'],
            ];
        }

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);

            if ($searchValue === null || $searchValue === '') continue;

            switch ($config['type']) {
                case 'like':
                    $query->where($config['field'], 'like', "%{$searchValue}%");
                    break;
                case 'equal':
                    $query->where($config['field'], '=', $searchValue);
                    break;
                case 'relation':
                    $relation = $config['relation'];
                    $field = str_replace($relation . '.', '', $config['field']);
                    $query->whereHas($relation, function ($q) use ($field, $searchValue) {
                        $q->where($field, 'like', "%{$searchValue}%");
                    });
                    break;
            }
        }

        return $query;
    }
}
