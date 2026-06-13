<?php

namespace App\Repositories\ClientManagement;

use App\Models\BillingDevice;
use App\DTOs\ClientManagement\BillingDeviceFilterData;

class BillingDeviceRepository
{
    /**
     * Get filtered query for the Billing Device list.
     */
    public function getFilteredData(BillingDeviceFilterData $filters)
    {
        $query = BillingDevice::query()->with(['company']);

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
    public function applySearchFilters($query, BillingDeviceFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'device_id', 'type' => 'like'],
                3 => ['field' => 'phone', 'type' => 'like'],
                4 => ['field' => 'vehicle_uid', 'type' => 'like'],
                5 => ['field' => 'vehicle_name', 'type' => 'like'],
                6 => ['field' => 'imei', 'type' => 'like'],
                7 => ['field' => 'speed_limit', 'type' => 'like'],
                8 => ['field' => 'sim_network', 'type' => 'like'],
                9 => ['field' => 'category', 'type' => 'like'],
                10 => ['field' => 'model', 'type' => 'like'],
                11 => ['field' => 'subscription_expiry_date', 'type' => 'like'],
                12 => ['field' => 'installation_date', 'type' => 'like'],
                13 => ['field' => 'expired_date', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'device_id', 'type' => 'like'],
                2 => ['field' => 'phone', 'type' => 'like'],
                3 => ['field' => 'vehicle_uid', 'type' => 'like'],
                4 => ['field' => 'vehicle_name', 'type' => 'like'],
                5 => ['field' => 'imei', 'type' => 'like'],
                6 => ['field' => 'speed_limit', 'type' => 'like'],
                7 => ['field' => 'sim_network', 'type' => 'like'],
                8 => ['field' => 'category', 'type' => 'like'],
                9 => ['field' => 'model', 'type' => 'like'],
                10 => ['field' => 'subscription_expiry_date', 'type' => 'like'],
                11 => ['field' => 'installation_date', 'type' => 'like'],
                12 => ['field' => 'expired_date', 'type' => 'like'],
            ];
        }

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);

            if ($searchValue === null || $searchValue === '') continue;

            switch ($config['type']) {
                case 'like':
                    $query->where($config['field'], 'like', "%{$searchValue}%");
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
