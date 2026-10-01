<?php

namespace App\Repositories\ClientManagement;

use App\Models\BillingNotification;
use App\DTOs\ClientManagement\BillingNotificationFilterData;

class BillingNotificationRepository
{
    /**
     * Get filtered query for the Billing Notification list.
     */
    public function getFilteredData(BillingNotificationFilterData $filters)
    {
        $query = BillingNotification::query()
            ->selectRaw("
                MIN(billing_notifications.id) as id,
                billing_notifications.company_id,
                COALESCE(bd.code_billing, bs.code_billing) as code_billing_calc,
                COUNT(billing_notifications.id) as total_items,
                SUM(CASE WHEN billing_notifications.billable_type LIKE '%BillingDevice%' THEN 1 ELSE 0 END) as total_devices,
                SUM(CASE WHEN billing_notifications.billable_type LIKE '%BillingSimcard%' THEN 1 ELSE 0 END) as total_simcards,
                MAX(billing_notifications.notification_date) as notification_date,
                GROUP_CONCAT(DISTINCT billing_notifications.message SEPARATOR '<br>') as message,
                MAX(CASE 
                    WHEN bd.id IS NOT NULL AND bd.expired_date < CURDATE() THEN 1 
                    WHEN bs.id IS NOT NULL AND bs.expired_date < CURDATE() THEN 1 
                    ELSE 0 
                END) as is_expired,
                MAX(CASE
                    WHEN EXISTS (
                        SELECT 1 FROM invoice_clients ic
                        INNER JOIN invoice_client_details icd ON ic.id = icd.invoice_client_id
                        WHERE YEAR(ic.invoice_date) = YEAR(billing_notifications.notification_date)
                          AND MONTH(ic.invoice_date) = MONTH(billing_notifications.notification_date)
                          AND icd.code_billing = COALESCE(bd.code_billing, bs.code_billing)
                    ) THEN 1
                    ELSE 0
                END) as has_invoice_this_month
            ")
            ->leftJoin('billing_devices as bd', function ($join) {
                $join->on('billing_notifications.billable_id', '=', 'bd.id')
                     ->where('billing_notifications.billable_type', 'like', '%BillingDevice%');
            })
            ->leftJoin('billing_simcards as bs', function ($join) {
                $join->on('billing_notifications.billable_id', '=', 'bs.id')
                     ->where('billing_notifications.billable_type', 'like', '%BillingSimcard%');
            })
            ->whereNull('billing_notifications.deleted_at')
            ->groupBy('billing_notifications.company_id', \Illuminate\Support\Facades\DB::raw('COALESCE(bd.code_billing, bs.code_billing)'))
            ->with(['company'])
            ->orderByRaw('code_billing_calc IS NULL, code_billing_calc ASC');

        $user = backpack_user();
        if ($user && !$user->canAccessAllCompanies()) {
            $accessibleCompanyIds = $user->getAccessibleCompanyIds();
            $query->whereIn('billing_notifications.company_id', $accessibleCompanyIds);
        }

        // Scoping based on company
        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('billing_notifications.company_id', $filters->company_id);
        }

        // Apply DataTables search filters
        return $this->applySearchFilters($query, $filters);
    }

    /**
     * Apply DataTables column search filters.
     */
    public function applySearchFilters($query, BillingNotificationFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        // Map indeks kolom ke field database (Index 1 is company)
        // 0: row_number, 1: company, 2: code_billing, 3: total_items, 4: notification_date, 5: message, 6: action
        $filterMap = [
            1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
            2 => ['field' => 'code_billing', 'type' => 'having_like'],
            3 => ['field' => 'total_items', 'type' => 'having_exact'],
            4 => ['field' => 'notification_date', 'type' => 'having_like'],
            5 => ['field' => 'message', 'type' => 'having_like'],
        ];

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);

            if ($searchValue === null || $searchValue === '') continue;

            switch ($config['type']) {
                case 'having_like':
                    if ($config['field'] === 'code_billing') {
                        $query->havingRaw("COALESCE(bd.code_billing, bs.code_billing) LIKE ?", ["%{$searchValue}%"]);
                    } elseif ($config['field'] === 'notification_date') {
                        $query->havingRaw("DATE_FORMAT(MAX(billing_notifications.notification_date), '%d/%m/%Y') LIKE ? OR MAX(billing_notifications.notification_date) LIKE ?", ["%{$searchValue}%", "%{$searchValue}%"]);
                    } elseif ($config['field'] === 'message') {
                        $query->havingRaw("GROUP_CONCAT(DISTINCT billing_notifications.message SEPARATOR ' ') LIKE ?", ["%{$searchValue}%"]);
                    }
                    break;
                case 'having_exact':
                    if ($config['field'] === 'total_items') {
                        $query->havingRaw("COUNT(billing_notifications.id) = ?", [(int) $searchValue]);
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
     * Get IDs of billing notifications that have been paid.
     */
    public function getPaidNotificationIds(?int $limit = null): array
    {
        $query = \Illuminate\Support\Facades\DB::table('billing_notifications')
            ->join('invoice_clients', function ($join) {
                $join->on('invoice_clients.type_device', '=', 'billing_notifications.billable_type')
                    ->whereRaw('YEAR(invoice_clients.invoice_date) = YEAR(billing_notifications.notification_date)')
                    ->whereRaw('MONTH(invoice_clients.invoice_date) = MONTH(billing_notifications.notification_date)');
            })
            ->join('invoice_client_details', 'invoice_clients.id', '=', 'invoice_client_details.invoice_client_id')
            ->join('account_transactions', function ($join) {
                $join->on('account_transactions.reference_id', '=', 'invoice_clients.id')
                    ->where('account_transactions.reference_type', '=', 'App\\Models\\InvoiceClient');
            })
            ->where('invoice_clients.status', '=', 'Paid')
            ->whereNull('billing_notifications.deleted_at')
            ->whereColumn('invoice_client_details.name', \Illuminate\Support\Facades\DB::raw("
                (CASE 
                    WHEN billing_notifications.billable_type = 'App\\\\Models\\\\BillingDevice' THEN (
                        SELECT device_id FROM billing_devices WHERE billing_devices.id = billing_notifications.billable_id LIMIT 1
                    )
                    WHEN billing_notifications.billable_type = 'App\\\\Models\\\\BillingSimcard' THEN (
                        SELECT device_profile_id FROM billing_simcards WHERE billing_simcards.id = billing_notifications.billable_id LIMIT 1
                    )
                END)
            "));

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->pluck('billing_notifications.id')->toArray();
    }

    /**
     * Get count of grouped billing notifications considering user company access.
     */
    public function getGroupedNotificationCount(): int
    {
        $filters = new BillingNotificationFilterData();
        $query = $this->getFilteredData($filters);

        return \Illuminate\Support\Facades\DB::table(
            $query->toBase()->cloneWithout(['orders', 'limit', 'offset']),
            'grouped_notifications'
        )->count();
    }
}
