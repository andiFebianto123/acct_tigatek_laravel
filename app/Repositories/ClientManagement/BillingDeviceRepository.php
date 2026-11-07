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
        $query = BillingDevice::query()->with(['company', 'client']);

        $user = backpack_user();
        if ($user && !$user->canAccessAllCompanies()) {
            $accessibleCompanyIds = $user->getAccessibleCompanyIds();
            $query->whereIn('billing_devices.company_id', $accessibleCompanyIds);
        }

        // Scoping based on company
        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('billing_devices.company_id', $filters->company_id);
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

        // Map indeks kolom ke field database (Index 0 is row_number, Index 1 is bulk_action, Index 2 is company)
        $filterMap = [
            2 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
            3 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
            4 => ['field' => 'code_billing', 'type' => 'like'],
            5 => ['field' => 'device_id', 'type' => 'like'],
            6 => ['field' => 'phone', 'type' => 'like'],
            7 => ['field' => 'vehicle_uid', 'type' => 'like'],
            8 => ['field' => 'vehicle_name', 'type' => 'like'],
            9 => ['field' => 'imei', 'type' => 'like'],
            10 => ['field' => 'speed_limit', 'type' => 'like'],
            11 => ['field' => 'sim_network', 'type' => 'like'],
            12 => ['field' => 'category', 'type' => 'like'],
            13 => ['field' => 'model', 'type' => 'like'],
            14 => ['field' => 'subscription_expiry_date', 'type' => 'like'],
            15 => ['field' => 'installation_date', 'type' => 'like'],
            16 => ['field' => 'expired_date', 'type' => 'like'],
            17 => ['field' => 'reminder_date', 'type' => 'like'],
        ];

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
