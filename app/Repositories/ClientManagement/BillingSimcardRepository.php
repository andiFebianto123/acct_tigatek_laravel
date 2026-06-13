<?php

namespace App\Repositories\ClientManagement;

use App\Models\BillingSimcard;
use App\DTOs\ClientManagement\BillingSimcardFilterData;
use Carbon\Carbon;

class BillingSimcardRepository
{
    /**
     * Get filtered query for the Billing SIMCARD list.
     */
    public function getFilteredData(BillingSimcardFilterData $filters)
    {
        $query = BillingSimcard::query()->with(['company']);

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
    public function applySearchFilters($query, BillingSimcardFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'product', 'type' => 'like'],
                3 => ['field' => 'device_name', 'type' => 'like'],
                4 => ['field' => 'technology', 'type' => 'like'],
                5 => ['field' => 'device_profile_id', 'type' => 'like'],
                6 => ['field' => 'iccid', 'type' => 'like'],
                7 => ['field' => 'msisdn', 'type' => 'like'],
                8 => ['field' => 'status', 'type' => 'like'],
                9 => ['field' => 'simcard_status', 'type' => 'simcard_status'],
                10 => ['field' => 'rate_plan', 'type' => 'like'],
                11 => ['field' => 'subscription_expiry_date', 'type' => 'like'],
                12 => ['field' => 'installation_date', 'type' => 'like'],
                13 => ['field' => 'expired_date', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'product', 'type' => 'like'],
                2 => ['field' => 'device_name', 'type' => 'like'],
                3 => ['field' => 'technology', 'type' => 'like'],
                4 => ['field' => 'device_profile_id', 'type' => 'like'],
                5 => ['field' => 'iccid', 'type' => 'like'],
                6 => ['field' => 'msisdn', 'type' => 'like'],
                7 => ['field' => 'status', 'type' => 'like'],
                8 => ['field' => 'simcard_status', 'type' => 'simcard_status'],
                9 => ['field' => 'rate_plan', 'type' => 'like'],
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
                case 'simcard_status':
                    $today = Carbon::now()->format('Y-m-d');
                    $lowerValue = strtolower(trim($searchValue));
                    if ($lowerValue === 'active') {
                        $query->whereDate('expired_date', '>=', $today);
                    } else if ($lowerValue === 'deactive') {
                        $query->where(function ($q) use ($today) {
                            $q->whereDate('expired_date', '<', $today)
                              ->orWhereNull('expired_date');
                        });
                    }
                    break;
            }
        }

        return $query;
    }
}
