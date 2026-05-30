<?php

namespace App\Repositories\ClientManagement;

use App\Models\ClientPo;
use App\Models\InvoiceClient;
use App\DTOs\ClientManagement\ClientPoFilterData;
use Illuminate\Support\Facades\DB;

class ClientPoRepository
{
    /**
     * Get filtered data for the Client PO list.
     */
    public function getFilteredData(ClientPoFilterData $filters)
    {
        $query = ClientPo::query()->with(['client', 'company']);

        // Filter Sidebar/Plugin
        if ($filters->status_invoice !== null) {
            $invoice = InvoiceClient::select(DB::raw('client_po_id, count(invoice_number) as total_invoice'))
                ->groupBy('client_po_id');
            $query->leftJoinSub($invoice, 'invoices', function ($join) {
                $join->on('client_po.id', 'invoices.client_po_id');
            });
            if ($filters->status_invoice == 1) {
                $query->where('invoices.total_invoice', '>=', $filters->status_invoice);
            } else {
                $query->whereNull('invoices.total_invoice');
            }
        }

        if ($filters->date_po !== null && $filters->date_po !== '') {
            $query->where('date_po', 'like', '%' . $filters->date_po . '%');
        }

        if ($filters->year && $filters->year != 'all') {
            $query->whereYear('date_po', $filters->year);
        }

        // Apply Column Search Filters
        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Apply DataTables column search filters.
     */
    public function applySearchFilters($query, ClientPoFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        // Mendeteksi apakah user adalah Super Admin (kolom bergeser)
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
                10 => ['field' => 'date_range', 'type' => 'custom'], // start_date & end_date
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
                9 => ['field' => 'date_range', 'type' => 'custom'], // start_date & end_date
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

    /**
     * Get summary values for the PO list.
     */
    public function getSummaryValues(ClientPoFilterData $filters)
    {
        $query = $this->getFilteredData($filters);
        $result = $query->select(DB::raw("SUM(job_value) as total_job_value, SUM(job_value_include_ppn) as total_job_value_ppn"))->first();

        return [
            'total_job_value' => $result->total_job_value ?? 0,
            'total_job_value_ppn' => $result->total_job_value_ppn ?? 0,
        ];
    }

    /**
     * Find Client PO by ID with client relationship.
     */
    public function findWithClient(int $id)
    {
        return ClientPo::with('client')->find($id);
    }
}
