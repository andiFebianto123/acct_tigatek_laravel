<?php

namespace App\Repositories\ClientManagement;

use App\Models\BillingNotification;
use App\DTOs\ClientManagement\BillingNotificationFilterData;

class BillingNotificationRepository
{
    /**
     * Get filtered query for the Billing Notification list.
     */
    public function getFilteredData(BillingNotificationFilterData $filters)
    {
        $query = BillingNotification::query()->with(['company', 'billable']);

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
    public function applySearchFilters($query, BillingNotificationFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'billable_type', 'type' => 'like'],
                3 => ['field' => 'billable_id', 'type' => 'like'],
                4 => ['field' => 'notification_date', 'type' => 'like'],
                5 => ['field' => 'message', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'billable_type', 'type' => 'like'],
                2 => ['field' => 'billable_id', 'type' => 'like'],
                3 => ['field' => 'notification_date', 'type' => 'like'],
                4 => ['field' => 'message', 'type' => 'like'],
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
