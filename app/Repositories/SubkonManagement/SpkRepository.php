<?php

namespace App\Repositories\SubkonManagement;

use App\Models\Spk;
use App\Models\Voucher;
use App\Http\Helpers\CustomHelper;
use App\DTOs\SubkonManagement\SpkFilterData;
use Illuminate\Support\Facades\DB;

class SpkRepository
{
    /**
     * Get summary prices for the dashboard.
     */
    public function getTotalPrices(string|null $filterYear = 'all'): array
    {
        $total_open = Spk::query()->where('status', Spk::OPEN);
        if ($filterYear != 'all' && $filterYear != null) {
            $total_open = $total_open->where(DB::raw("YEAR(date_spk)"), $filterYear);
        }
        $sum_open = $total_open->sum('total_value_with_tax');

        $total_closed = Spk::query()->where('status', Spk::CLOSE);
        if ($filterYear != 'all' && $filterYear != null) {
            $total_closed = $total_closed->where(DB::raw("YEAR(date_spk)"), $filterYear);
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
    public function applySearchFilters($query, SpkFilterData $filters)
    {
        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        $filterMap = [];
        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'no_spk', 'type' => 'like'],
                3 => ['field' => 'date_spk', 'type' => 'like'],
                4 => ['field' => 'subkon.name', 'type' => 'relation', 'relation' => 'subkon'],
                5 => ['field' => 'work_code', 'type' => 'like'],
                6 => ['field' => 'job_name', 'type' => 'like'],
                7 => ['field' => 'job_description', 'type' => 'like'],
                8 => ['field' => 'job_value', 'type' => 'like'],
                9 => ['field' => 'tax_ppn', 'type' => 'like'],
                10 => ['field' => 'total_value_with_tax', 'type' => 'like'],
                11 => ['field' => 'due_date', 'type' => 'like'],
                12 => ['field' => 'status', 'type' => 'like'],
                14 => ['field' => 'additional_info', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'no_spk', 'type' => 'like'],
                2 => ['field' => 'date_spk', 'type' => 'like'],
                3 => ['field' => 'subkon.name', 'type' => 'relation', 'relation' => 'subkon'],
                4 => ['field' => 'work_code', 'type' => 'like'],
                5 => ['field' => 'job_name', 'type' => 'like'],
                6 => ['field' => 'job_description', 'type' => 'like'],
                7 => ['field' => 'job_value', 'type' => 'like'],
                8 => ['field' => 'tax_ppn', 'type' => 'like'],
                9 => ['field' => 'total_value_with_tax', 'type' => 'like'],
                10 => ['field' => 'due_date', 'type' => 'like'],
                11 => ['field' => 'status', 'type' => 'like'],
                13 => ['field' => 'additional_info', 'type' => 'like'],
            ];
        }

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);
            if ($searchValue === null) continue;

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
     * Get filtered data for export or lists using DTO.
     */
    public function getFilteredData(SpkFilterData $filters)
    {
        $query = Spk::query();

        if ($filters->tab === 'open' || $filters->tab === 'close') {
            $query->where('status', $filters->tab);
        }

        if ($filters->year !== 'all' && $filters->year !== null) {
            $query->whereYear('date_spk', $filters->year);
        }

        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Check if an SPK has an associated Voucher.
     */
    public function hasVoucher(int $id): bool
    {
        return Voucher::where('reference_type', Spk::class)
            ->where('reference_id', $id)
            ->exists();
    }
}
