<?php

namespace App\Repositories\SubkonManagement;

use App\Models\Subkon;
use App\Models\PurchaseOrder;
use App\Models\Spk;
use Illuminate\Support\Facades\DB;

class SubkonRepository
{
    /**
     * Apply ordering by PO count.
     */
    public function orderByPoCount($query, $columnDirection)
    {
        $po = PurchaseOrder::select(DB::raw('subkon_id, count(po_number) as total_po'))
            ->groupBy('subkon_id');
            
        return $query->leftJoinSub($po, 'po', function ($join) {
            $join->on('po.subkon_id', 'subkons.id');
        })->select('subkons.*')->orderBy('po.total_po', $columnDirection);
    }

    /**
     * Apply ordering by SPK count.
     */
    public function orderBySpkCount($query, $columnDirection)
    {
        $spk = Spk::select(DB::raw('subkon_id, count(no_spk) as total_spk'))
            ->groupBy('subkon_id');
            
        return $query->leftJoinSub($spk, 'spk', function ($join) {
            $join->on('spk.subkon_id', 'subkons.id');
        })->select('subkons.*')->orderBy('spk.total_spk', $columnDirection);
    }

    /**
     * Apply year filters based on PO or SPK relations.
     */
    public function applyYearFilter($query, $filterYear)
    {
        if ($filterYear === 'all') {
            return $query;
        }

        return $query->where(function ($q) use ($filterYear) {
            $q->whereHas('purchase_orders', function ($subQ) use ($filterYear) {
                $subQ->whereYear('date_po', $filterYear);
            })
            ->orWhereHas('spks', function ($subQ) use ($filterYear) {
                $subQ->whereYear('date_spk', $filterYear);
            });
        });
    }

    /**
     * Get filtered data for export.
     */
    public function getExportData($request)
    {
        $query = Subkon::query();

        if ($request->has('filter_year')) {
            $query = $this->applyYearFilter($query, $request->filter_year);
        }

        return $query;
    }
}
