<?php

namespace App\Repositories\ClientManagement;

use App\Models\Bast;
use App\DTOs\ClientManagement\BastFilterData;

class BastRepository
{
    /**
     * Get filtered query for the BAST list.
     */
    public function getFilteredData(BastFilterData $filters)
    {
        $query = Bast::query()->with(['client', 'company', 'client_po']);

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
    public function applySearchFilters($query, BastFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'number', 'type' => 'like'],
                3 => ['field' => 'date', 'type' => 'like'],
                4 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
                5 => ['field' => 'first_party', 'type' => 'like'],
                6 => ['field' => 'description', 'type' => 'like'],
                7 => ['field' => 'qty', 'type' => 'like'],
                8 => ['field' => 'information', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'number', 'type' => 'like'],
                2 => ['field' => 'date', 'type' => 'like'],
                3 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
                4 => ['field' => 'first_party', 'type' => 'like'],
                5 => ['field' => 'description', 'type' => 'like'],
                6 => ['field' => 'qty', 'type' => 'like'],
                7 => ['field' => 'information', 'type' => 'like'],
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

    /**
     * Generate next BAST number.
     */
    public function generateNextNumber()
    {
        $settings = \App\Models\Setting::first();
        $bastPrefix = $settings?->bast_prefix ?? 'BAST';
        $monthYear = now()->format('my');
        $pattern = $bastPrefix . '/' . $monthYear . '/';
        $lastEntry = Bast::where('number', 'like', $pattern . '%')
            ->orderBy('number', 'desc')
            ->first();
        if ($lastEntry) {
            $parts = explode('/', $lastEntry->number);
            $lastIndex = (int) end($parts);
            $nextIndex = $lastIndex + 1;
        } else {
            $nextIndex = 1;
        }
        return $pattern . sprintf('%02d', $nextIndex);
    }
}
