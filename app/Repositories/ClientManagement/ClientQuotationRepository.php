<?php

namespace App\Repositories\ClientManagement;

use App\Models\ClientQuotation;
use App\DTOs\ClientManagement\ClientQuotationFilterData;
use Illuminate\Support\Facades\DB;

class ClientQuotationRepository
{
    public function getFilteredData(ClientQuotationFilterData $filters)
    {
        $query = ClientQuotation::query()->with(['client', 'company']);

        if ($filters->date_po !== null && $filters->date_po !== '') {
            $query->where('date_po', 'like', '%' . $filters->date_po . '%');
        }

        if ($filters->year && $filters->year != 'all') {
            $query->whereYear('date_po', $filters->year);
        }

        return $this->applySearchFilters($query, $filters);
    }

    public function applySearchFilters($query, ClientQuotationFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'work_code', 'type' => 'like'],
                3 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
                4 => ['field' => 'reimburse_type', 'type' => 'like'],
                5 => ['field' => 'po_number', 'type' => 'like'],
                6 => ['field' => 'job_name', 'type' => 'like'],
                7 => ['field' => 'rap_value', 'type' => 'like'],
                8 => ['field' => 'job_value', 'type' => 'like'],
                9 => ['field' => 'job_value_include_ppn', 'type' => 'like'],
                10 => ['field' => 'date_range', 'type' => 'custom'],
                11 => ['field' => 'date_po', 'type' => 'like'],
                12 => ['field' => 'document_path', 'type' => 'like'],
                13 => ['field' => 'category', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'work_code', 'type' => 'like'],
                2 => ['field' => 'client.name', 'type' => 'relation', 'relation' => 'client'],
                3 => ['field' => 'reimburse_type', 'type' => 'like'],
                4 => ['field' => 'po_number', 'type' => 'like'],
                5 => ['field' => 'job_name', 'type' => 'like'],
                6 => ['field' => 'rap_value', 'type' => 'like'],
                7 => ['field' => 'job_value', 'type' => 'like'],
                8 => ['field' => 'job_value_include_ppn', 'type' => 'like'],
                9 => ['field' => 'date_range', 'type' => 'custom'],
                10 => ['field' => 'date_po', 'type' => 'like'],
                11 => ['field' => 'document_path', 'type' => 'like'],
                12 => ['field' => 'category', 'type' => 'like'],
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
                case 'custom':
                    if ($config['field'] === 'date_range') {
                        $query->where(function ($q) use ($searchValue) {
                            $q->where('start_date', 'like', "%{$searchValue}%")
                                ->orWhere('end_date', 'like', "%{$searchValue}%");
                        });
                    }
                    break;
            }
        }

        return $query;
    }

    public function getSummaryValues(ClientQuotationFilterData $filters)
    {
        $query = $this->getFilteredData($filters);
        $result = $query->select(DB::raw("SUM(job_value) as total_job_value, SUM(job_value_include_ppn) as total_job_value_ppn"))->first();

        return [
            'total_job_value' => $result->total_job_value ?? 0,
            'total_job_value_ppn' => $result->total_job_value_ppn ?? 0,
        ];
    }

    public function getQuotationsForSelection(\App\DTOs\ClientManagement\QuotationSelectionRequestData $data)
    {
        $query = ClientQuotation::whereDoesntHave('clientPos');

        if ($data->company_id) {
            $query->where('company_id', $data->company_id);
        }

        // Global Search
        if ($data->search) {
            $query->where(function($q) use ($data) {
                $q->where('work_code', 'like', "%{$data->search}%")
                  ->orWhere('job_name', 'like', "%{$data->search}%")
                  ->orWhereHas('client', function($q2) use ($data) {
                      $q2->where('name', 'like', "%{$data->search}%");
                  });
            });
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        // Sorting
        $columns = ['id', 'work_code', 'client_id', 'job_name', 'job_value', 'tax_ppn', 'job_value_include_ppn'];
        if ($data->order_column !== null && isset($columns[$data->order_column])) {
            $columnName = $columns[$data->order_column];
            if ($columnName == 'client_id') {
                $query->join('clients', 'client_quotations.client_id', '=', 'clients.id')
                      ->orderBy('clients.name', $data->order_dir)
                      ->select('client_quotations.*');
            } else {
                $query->orderBy($columnName, $data->order_dir);
            }
        }

        $quotations = $query->with('client')->skip($data->start)->take($data->length)->get();

        return [
            'total' => $totalData,
            'filtered' => $totalFiltered,
            'data' => $quotations
        ];
    }

    public function getQuotationDetailsByIds(array $ids)
    {
        return ClientQuotation::whereIn('id', $ids)->get();
    }

    /**
     * Generate next Quotation number.
     */
    public function generateNextNumber()
    {
        $settings = \App\Models\Setting::first();
        $prefix = $settings?->quotation_prefix ?? 'QUO';
        $monthYear = now()->format('my');
        $pattern = $prefix . '/' . $monthYear . '/';
        $lastEntry = ClientQuotation::where('po_number', 'like', $pattern . '%')
            ->orderBy('po_number', 'desc')
            ->first();
        if ($lastEntry) {
            $parts = explode('/', $lastEntry->po_number);
            $lastIndex = (int) end($parts);
            $nextIndex = $lastIndex + 1;
        } else {
            $nextIndex = 1;
        }
        return $pattern . sprintf('%02d', $nextIndex);
    }
}
