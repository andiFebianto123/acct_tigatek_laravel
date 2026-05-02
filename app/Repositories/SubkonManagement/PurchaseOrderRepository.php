<?php

namespace App\Repositories\SubkonManagement;

use App\Models\PurchaseOrder;
use App\Http\Helpers\CustomHelper;
use App\DTOs\SubkonManagement\PurchaseOrderFilterData;
use Illuminate\Support\Facades\DB;

class PurchaseOrderRepository
{
    /**
     * Get summary prices for the dashboard.
     */
    public function getTotalPrices(string|null $filterYear = 'all'): array
    {
        $total_open = PurchaseOrder::query()->where('status', PurchaseOrder::OPEN);
        if ($filterYear != 'all' && $filterYear != null) {
            $total_open = $total_open->where(DB::raw("YEAR(date_po)"), $filterYear);
        }
        $sum_open = $total_open->sum('total_value_with_tax');

        $total_closed = PurchaseOrder::query()->where('status', PurchaseOrder::CLOSE);
        if ($filterYear != 'all' && $filterYear != null) {
            $total_closed = $total_closed->where(DB::raw("YEAR(date_po)"), $filterYear);
        }
        $sum_closed = $total_closed->sum('total_value_with_tax');

        return [
            'total_open' => CustomHelper::formatRupiahWithCurrency($sum_open),
            'total_closed' => CustomHelper::formatRupiahWithCurrency($sum_closed)
        ];
    }

    /**
     * Apply search filters to the query using DTO.
     */
    public function applySearchFilters($query, PurchaseOrderFilterData $filters)
    {
        // Mapping index columns to database fields
        $filterMap = [
            1 => ['field' => 'po_number', 'type' => 'like'],
            2 => ['field' => 'date_po', 'type' => 'like'],
            3 => ['field' => 'subkon.name', 'type' => 'relation'],
            4 => ['field' => 'work_code', 'type' => 'like'],
            5 => ['field' => 'job_name', 'type' => 'like'],
            6 => ['field' => 'job_description', 'type' => 'like'],
            7 => ['field' => 'job_value', 'type' => 'like'],
            8 => ['field' => 'tax_ppn', 'type' => 'like'],
            9 => ['field' => 'total_value_with_tax', 'type' => 'like'],
            10 => ['field' => 'due_date', 'type' => 'due_date_special'],
            11 => ['field' => 'status', 'type' => 'like'],
            13 => ['field' => 'additional_info', 'type' => 'like'],
        ];

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);
            if ($searchValue === null) continue;

                switch ($config['type']) {
                    case 'like':
                        $query->where($config['field'], 'like', "%{$searchValue}%");
                        break;
                    case 'relation':
                        $query->whereHas('subkon', function ($q) use ($searchValue) {
                            $q->where('name', 'like', "%{$searchValue}%");
                        });
                        break;
                    case 'due_date_special':
                        $query->where(function ($q) use ($searchValue) {
                            $q->where('due_date', 'like', "%{$searchValue}%")
                                ->orWhere('date_po', 'like', "%{$searchValue}%");
                        });
                        break;
                }
        }
        return $query;
    }

    /**
     * Get filtered data for export or lists using DTO.
     */
    public function getFilteredData(PurchaseOrderFilterData $filters)
    {
        $query = PurchaseOrder::query();

        // Filter Status dari Tab
        if ($filters->tab === 'open' || $filters->tab === 'close') {
            $query->where('status', $filters->tab);
        }

        // Filter Tahun
        if ($filters->year !== 'all' && $filters->year !== null) {
            $query->whereYear('date_po', $filters->year);
        }

        // Terapkan filter pencarian tabel (reuse logic)
        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Check if a Purchase Order has an associated Voucher.
     */
    public function hasVoucher(int $id): bool
    {
        return \App\Models\Voucher::where('reference_type', \App\Models\PurchaseOrder::class)
            ->where('reference_id', $id)
            ->exists();
    }
}
