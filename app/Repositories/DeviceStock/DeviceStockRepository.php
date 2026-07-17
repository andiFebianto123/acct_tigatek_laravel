<?php

namespace App\Repositories\DeviceStock;

use App\Models\DeviceStock;
use Illuminate\Database\Eloquent\Builder;

class DeviceStockRepository
{
    /**
     * Apply filter for category
     */
    public function applyCategoryFilter(Builder $query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get all entries for export
     */
    public function getExportData($request)
    {
        $query = DeviceStock::with('category');

        if ($request->has('category_id') && $request->input('category_id') !== '') {
            $query = $this->applyCategoryFilter($query, $request->input('category_id'));
        }

        return $query;
    }
}
