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
        $query = Bast::query()->with(['client', 'company', 'client_po', 'referenceable']);

        $user = backpack_user();
        if ($user && !$user->canAccessAllCompanies()) {
            $accessibleCompanyIds = $user->getAccessibleCompanyIds();
            $query->whereIn('basts.company_id', $accessibleCompanyIds);
        }

        // Scoping based on company
        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('basts.company_id', $filters->company_id);
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

        // Map indeks kolom ke field database (Index 1 is always company)
        $filterMap = [
            1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
            2 => ['field' => 'number', 'type' => 'like'],
            3 => ['field' => 'date', 'type' => 'like'],
            4 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
            5 => ['field' => 'pic', 'type' => 'like'],
            6 => ['field' => 'phone', 'type' => 'like'],
            7 => ['field' => 'first_party', 'type' => 'like'],
            8 => ['field' => 'description', 'type' => 'like'],
            9 => ['field' => 'qty', 'type' => 'like'],
            10 => ['field' => 'information', 'type' => 'like'],
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
