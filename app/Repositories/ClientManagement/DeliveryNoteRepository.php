<?php

namespace App\Repositories\ClientManagement;

use App\Models\DeliveryNote;
use App\DTOs\ClientManagement\DeliveryNoteFilterData;

class DeliveryNoteRepository
{
    /**
     * Get filtered query for the Delivery Note list.
     */
    public function getFilteredData(DeliveryNoteFilterData $filters)
    {
        $query = DeliveryNote::query()->with(['client', 'company', 'client_po']);

        // Scoping based on company
        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('company_id', $filters->company_id);
        } else if (backpack_user() && !backpack_user()->hasRole('Super Admin')) {
            $query->where('company_id', backpack_user()->company_id);
        }

        // Standard filter date
        if ($filters->date !== null && $filters->date !== '') {
            $query->where('date', 'like', '%' . $filters->date . '%');
        }

        // Standard filter year
        if ($filters->year && $filters->year != 'all') {
            $query->whereYear('date', $filters->year);
        }

        // Apply DataTables search filters
        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Apply DataTables column search filters.
     */
    public function applySearchFilters($query, DeliveryNoteFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'number', 'type' => 'like'],
                3 => ['field' => 'date', 'type' => 'like'],
                4 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
                5 => ['field' => 'description', 'type' => 'like'],
                6 => ['field' => 'qty', 'type' => 'like'],
                7 => ['field' => 'information', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'number', 'type' => 'like'],
                2 => ['field' => 'date', 'type' => 'like'],
                3 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
                4 => ['field' => 'description', 'type' => 'like'],
                5 => ['field' => 'qty', 'type' => 'like'],
                6 => ['field' => 'information', 'type' => 'like'],
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
