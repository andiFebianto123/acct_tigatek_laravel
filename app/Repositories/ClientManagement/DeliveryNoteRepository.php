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

        $user = backpack_user();
        if ($user && !$user->canAccessAllCompanies()) {
            $accessibleCompanyIds = $user->getAccessibleCompanyIds();
            $query->whereIn('delivery_notes.company_id', $accessibleCompanyIds);
        }

        // Scoping based on company
        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('delivery_notes.company_id', $filters->company_id);
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

        // Map indeks kolom ke field database (Index 1 is always company)
        $filterMap = [
            1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
            2 => ['field' => 'number', 'type' => 'like'],
            3 => ['field' => 'date', 'type' => 'like'],
            4 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
            5 => ['field' => 'pic', 'type' => 'like'],
            6 => ['field' => 'phone', 'type' => 'like'],
            7 => ['field' => 'reference_type', 'type' => 'like'],
            8 => ['field' => 'description', 'type' => 'like'],
            9 => ['field' => 'information', 'type' => 'like'],
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
     * Generate next Delivery Note number.
     */
    public function generateNextNumber()
    {
        $settings = \App\Models\Setting::first();
        $prefix = $settings?->surat_jalan_prefix ?? 'SJ';
        $monthYear = now()->format('my');
        $pattern = $prefix . '/' . $monthYear . '/';
        $lastEntry = DeliveryNote::where('number', 'like', $pattern . '%')
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
