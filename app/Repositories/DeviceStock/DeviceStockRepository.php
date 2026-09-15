<?php

namespace App\Repositories\DeviceStock;

use App\Models\DeviceStock;
use App\DTOs\DeviceStock\DeviceStockFilterData;
use Illuminate\Database\Eloquent\Builder;

class DeviceStockRepository
{
    /**
     * Get filtered data for stock list and export.
     */
    public function getFilteredData(DeviceStockFilterData $filters): Builder
    {
        $query = DeviceStock::with('category');

        if ($filters->category_id !== null && $filters->category_id !== '') {
            $query = $this->applyCategoryFilter($query, $filters->category_id);
        }

        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Apply DataTables column search filters.
     */
    public function applySearchFilters(Builder $query, DeviceStockFilterData $filters): Builder
    {
        if (empty($filters->columnFilters)) {
            return $query;
        }

        // Map column indices matching datatable-origin columns configuration:
        // 0: row_number (skipped)
        // 1: name
        // 2: code
        // 3: category_name (relation: category.name)
        // 4: currency_code
        // 5: qty
        // 6: sell_price
        // 7: buy_price
        // 8: action (skipped)
        $filterMap = [
            1 => ['field' => 'name', 'type' => 'like'],
            2 => ['field' => 'code', 'type' => 'like'],
            3 => ['field' => 'name', 'type' => 'relation', 'relation' => 'category'],
            4 => ['field' => 'currency_code', 'type' => 'like'],
            5 => ['field' => 'qty', 'type' => 'like'],
            6 => ['field' => 'sell_price', 'type' => 'like'],
            7 => ['field' => 'buy_price', 'type' => 'like'],
        ];

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);

            if ($searchValue === null || $searchValue === '') {
                continue;
            }

            switch ($config['type']) {
                case 'like':
                    $query->where($config['field'], 'like', "%{$searchValue}%");
                    break;
                case 'relation':
                    $relation = $config['relation'];
                    $field = $config['field'];
                    $query->whereHas($relation, function ($q) use ($field, $searchValue) {
                        $q->where($field, 'like', "%{$searchValue}%");
                    });
                    break;
            }
        }

        return $query;
    }

    /**
     * Apply filter for category
     */
    public function applyCategoryFilter(Builder $query, $categoryId): Builder
    {
        if ($categoryId !== null && $categoryId !== '') {
            return $query->where('category_id', $categoryId);
        }

        return $query;
    }

    /**
     * Get all entries for export
     */
    public function getExportData($request): Builder
    {
        $filters = DeviceStockFilterData::fromRequest($request);
        return $this->getFilteredData($filters);
    }
}
