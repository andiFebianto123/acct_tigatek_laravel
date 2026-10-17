<?php

namespace App\Repositories\ClientManagement;

use App\Models\BillingReminder;
use App\DTOs\ClientManagement\BillingReminderFilterData;
use Illuminate\Support\Facades\DB;

class BillingReminderRepository
{
    /**
     * Get aggregated billing reminders data grouped by company_id and code_billing.
     */
    public function getFilteredData(BillingReminderFilterData $filters)
    {
        $devicesSub = DB::table('billing_devices')
            ->selectRaw("id as item_id, company_id, code_billing, installation_date, expired_date, 'device' as item_type")
            ->whereNull('deleted_at')
            ->whereNotNull('code_billing')
            ->where('code_billing', '!=', '');

        $simcardsSub = DB::table('billing_simcards')
            ->selectRaw("id as item_id, company_id, code_billing, installation_date, expired_date, 'simcard' as item_type")
            ->whereNull('deleted_at')
            ->whereNotNull('code_billing')
            ->where('code_billing', '!=', '');

        $itemsUnion = $devicesSub->unionAll($simcardsSub);

        $query = BillingReminder::withTrashed()
            ->fromSub($itemsUnion, 'items')
            ->leftJoin('billing_reminders as br', function ($join) {
                $join->on('br.company_id', '=', 'items.company_id')
                     ->on('br.code_billing', '=', 'items.code_billing')
                     ->whereNull('br.deleted_at');
            })
            ->selectRaw("
                COALESCE(br.id, MIN(items.item_id)) as id,
                items.company_id,
                items.code_billing,
                COUNT(items.item_id) as total_items,
                SUM(CASE WHEN items.item_type = 'device' THEN 1 ELSE 0 END) as total_devices,
                SUM(CASE WHEN items.item_type = 'simcard' THEN 1 ELSE 0 END) as total_simcards,
                COALESCE(br.installation_date, MIN(items.installation_date)) as installation_date,
                br.start_date,
                COALESCE(br.end_date, MAX(items.expired_date)) as end_date,
                br.period,
                br.next_notification_date
            ")
            ->groupBy(
                'items.company_id',
                'items.code_billing',
                'br.id',
                'br.installation_date',
                'br.start_date',
                'br.end_date',
                'br.period',
                'br.next_notification_date'
            )
            ->with(['company'])
            ->orderBy('items.code_billing', 'ASC');

        $user = backpack_user();
        if ($user && !$user->canAccessAllCompanies()) {
            $accessibleCompanyIds = $user->getAccessibleCompanyIds();
            $query->whereIn('items.company_id', $accessibleCompanyIds);
        }

        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('items.company_id', $filters->company_id);
        }

        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Apply column-specific filters from DataTables.
     */
    public function applySearchFilters($query, BillingReminderFilterData $filters)
    {
        if (empty($filters->columnFilters)) {
            return $query;
        }

        // Column Index Mapping:
        // 0: row_number, 1: company, 2: code_billing, 3: total_items, 4: installation_date, 5: start_date, 6: end_date, 7: period, 8: action
        $filterMap = [
            1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
            2 => ['field' => 'code_billing', 'type' => 'having_like'],
            3 => ['field' => 'total_items', 'type' => 'having_exact'],
            4 => ['field' => 'installation_date', 'type' => 'having_date'],
            5 => ['field' => 'start_date', 'type' => 'having_date'],
            6 => ['field' => 'end_date', 'type' => 'having_date'],
            7 => ['field' => 'period', 'type' => 'having_like'],
        ];

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);
            if ($searchValue === null || $searchValue === '') {
                continue;
            }

            switch ($config['type']) {
                case 'having_like':
                    if ($config['field'] === 'code_billing') {
                        $query->havingRaw("items.code_billing LIKE ?", ["%{$searchValue}%"]);
                    } elseif ($config['field'] === 'period') {
                        $query->havingRaw("br.period LIKE ?", ["%{$searchValue}%"]);
                    }
                    break;
                case 'having_exact':
                    if ($config['field'] === 'total_items') {
                        $query->havingRaw("COUNT(items.item_id) = ?", [(int) $searchValue]);
                    }
                    break;
                case 'having_date':
                    if ($config['field'] === 'installation_date') {
                        $query->havingRaw("DATE_FORMAT(COALESCE(br.installation_date, MIN(items.installation_date)), '%d/%m/%Y') LIKE ? OR COALESCE(br.installation_date, MIN(items.installation_date)) LIKE ?", ["%{$searchValue}%", "%{$searchValue}%"]);
                    } elseif ($config['field'] === 'start_date') {
                        $query->havingRaw("DATE_FORMAT(br.start_date, '%d/%m/%Y') LIKE ? OR br.start_date LIKE ?", ["%{$searchValue}%", "%{$searchValue}%"]);
                    } elseif ($config['field'] === 'end_date') {
                        $query->havingRaw("DATE_FORMAT(COALESCE(br.end_date, MAX(items.expired_date)), '%d/%m/%Y') LIKE ? OR COALESCE(br.end_date, MAX(items.expired_date)) LIKE ?", ["%{$searchValue}%", "%{$searchValue}%"]);
                    }
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
     * Find existing saved reminder setting by code_billing and company_id.
     */
    public function findByCodeBilling(string $codeBilling, ?int $companyId = null): ?BillingReminder
    {
        $query = BillingReminder::where('code_billing', $codeBilling);
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return $query->first();
    }

    /**
     * Get aggregate device/simcard information for a code_billing.
     */
    public function getAggregateForCodeBilling(string $codeBilling, ?int $companyId = null): ?object
    {
        $devicesSub = DB::table('billing_devices')
            ->selectRaw("id as item_id, company_id, code_billing, installation_date, expired_date, 'device' as item_type")
            ->whereNull('deleted_at')
            ->where('code_billing', $codeBilling);

        $simcardsSub = DB::table('billing_simcards')
            ->selectRaw("id as item_id, company_id, code_billing, installation_date, expired_date, 'simcard' as item_type")
            ->whereNull('deleted_at')
            ->where('code_billing', $codeBilling);

        if ($companyId) {
            $devicesSub->where('company_id', $companyId);
            $simcardsSub->where('company_id', $companyId);
        }

        $itemsUnion = $devicesSub->unionAll($simcardsSub);

        return DB::query()
            ->fromSub($itemsUnion, 'items')
            ->selectRaw("
                items.company_id,
                items.code_billing,
                COUNT(items.item_id) as total_items,
                SUM(CASE WHEN items.item_type = 'device' THEN 1 ELSE 0 END) as total_devices,
                SUM(CASE WHEN items.item_type = 'simcard' THEN 1 ELSE 0 END) as total_simcards,
                MIN(items.installation_date) as installation_date,
                MAX(items.expired_date) as expired_date
            ")
            ->groupBy('items.company_id', 'items.code_billing')
            ->first();
    }
}
