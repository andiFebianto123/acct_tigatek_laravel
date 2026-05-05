<?php

namespace App\Repositories\ClientManagement;

use App\Models\Client;
use App\DTOs\ClientManagement\ClientFilterData;
use Illuminate\Support\Facades\DB;

class ClientRepository
{
    /**
     * Get filtered data for the client list.
     */
    public function getFilteredData(ClientFilterData $filters)
    {
        $query = Client::query()->with(['company', 'client_po']);

        // Filter Tahun (dari Sidebar/Plugin)
        if ($filters->year && $filters->year !== 'all') {
            $query->whereHas('client_po', function($q) use($filters){
                $q->whereYear('end_date', $filters->year);
            });
        }

        // Apply Column Search Filters
        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Apply DataTables column search filters.
     */
    public function applySearchFilters($query, ClientFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        // Map indeks kolom ke field database (sesuaikan dengan urutan di setupListOperation)
        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');
        
        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'name', 'type' => 'like'],
                3 => ['field' => 'address', 'type' => 'like'],
                4 => ['field' => 'npwp', 'type' => 'like'],
                5 => ['field' => 'phone', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'name', 'type' => 'like'],
                2 => ['field' => 'address', 'type' => 'like'],
                3 => ['field' => 'npwp', 'type' => 'like'],
                4 => ['field' => 'phone', 'type' => 'like'],
            ];
        }

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);

            if ($searchValue === null || $searchValue === '') continue;

            if ($config['type'] === 'like') {
                $query->where($config['field'], 'like', "%{$searchValue}%");
            } elseif ($config['type'] === 'relation') {
                $relation = $config['relation'];
                $field = str_replace($relation . '.', '', $config['field']);
                $query->whereHas($relation, function ($q) use ($field, $searchValue) {
                    $q->where($field, 'like', "%{$searchValue}%");
                });
            }
        }

        return $query;
    }
}
