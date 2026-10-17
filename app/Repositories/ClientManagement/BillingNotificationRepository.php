<?php

namespace App\Repositories\ClientManagement;

use App\Models\BillingNotification;
use App\DTOs\ClientManagement\BillingNotificationFilterData;
use Illuminate\Support\Facades\DB;

class BillingNotificationRepository
{
    /**
     * Get filtered query for the Billing Notification list.
     */
    public function getFilteredData(BillingNotificationFilterData $filters)
    {
        $query = BillingNotification::query()
            ->selectRaw("
                billing_notifications.id,
                CONCAT('NOTIF-', LPAD(billing_notifications.id, 5, '0')) as notification_code,
                billing_notifications.company_id,
                billing_notifications.code_billing,
                billing_notifications.notification_date,
                billing_notifications.message,
                (
                    (SELECT COUNT(*) FROM billing_devices bd WHERE bd.code_billing = billing_notifications.code_billing AND bd.company_id = billing_notifications.company_id AND bd.deleted_at IS NULL)
                    +
                    (SELECT COUNT(*) FROM billing_simcards bs WHERE bs.code_billing = billing_notifications.code_billing AND bs.company_id = billing_notifications.company_id AND bs.deleted_at IS NULL)
                ) as total_items,
                (SELECT COUNT(*) FROM billing_devices bd WHERE bd.code_billing = billing_notifications.code_billing AND bd.company_id = billing_notifications.company_id AND bd.deleted_at IS NULL) as total_devices,
                (SELECT COUNT(*) FROM billing_simcards bs WHERE bs.code_billing = billing_notifications.code_billing AND bs.company_id = billing_notifications.company_id AND bs.deleted_at IS NULL) as total_simcards,
                (
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM billing_reminders br 
                            WHERE br.code_billing = billing_notifications.code_billing 
                              AND br.company_id = billing_notifications.company_id 
                              AND br.end_date < CURDATE()
                        ) THEN 1
                        ELSE 0
                    END
                ) as is_expired,
                (
                    CASE
                        WHEN EXISTS (
                            SELECT 1 FROM invoice_clients ic
                            WHERE ic.billing_notification_id = billing_notifications.id
                        ) THEN 1
                        WHEN EXISTS (
                            SELECT 1 FROM invoice_clients ic
                            INNER JOIN invoice_client_details icd ON ic.id = icd.invoice_client_id
                            WHERE YEAR(ic.invoice_date) = YEAR(billing_notifications.notification_date)
                              AND MONTH(ic.invoice_date) = MONTH(billing_notifications.notification_date)
                              AND icd.code_billing = billing_notifications.code_billing
                        ) THEN 1
                        ELSE 0
                    END
                ) as has_invoice_this_month,
                (
                    SELECT ic.status FROM invoice_clients ic
                    LEFT JOIN invoice_client_details icd ON ic.id = icd.invoice_client_id
                    WHERE (ic.billing_notification_id = billing_notifications.id)
                       OR (YEAR(ic.invoice_date) = YEAR(billing_notifications.notification_date)
                           AND MONTH(ic.invoice_date) = MONTH(billing_notifications.notification_date)
                           AND icd.code_billing = billing_notifications.code_billing)
                    ORDER BY ic.id DESC LIMIT 1
                ) as invoice_payment_status,
                (
                    SELECT ic.invoice_number FROM invoice_clients ic
                    LEFT JOIN invoice_client_details icd ON ic.id = icd.invoice_client_id
                    WHERE (ic.billing_notification_id = billing_notifications.id)
                       OR (YEAR(ic.invoice_date) = YEAR(billing_notifications.notification_date)
                           AND MONTH(ic.invoice_date) = MONTH(billing_notifications.notification_date)
                           AND icd.code_billing = billing_notifications.code_billing)
                    ORDER BY ic.id DESC LIMIT 1
                ) as invoice_number
            ")
            ->whereNull('billing_notifications.deleted_at')
            // Sembunyikan notifikasi jika invoice recurring sudah dibuat dan statusnya sudah 'Paid' (lunas)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('invoice_clients as ic')
                    ->leftJoin('invoice_client_details as icd', 'ic.id', '=', 'icd.invoice_client_id')
                    ->where('ic.status', '=', 'Paid')
                    ->where(function ($w) {
                        $w->whereColumn('ic.billing_notification_id', 'billing_notifications.id')
                          ->orWhere(function ($sub) {
                              $sub->whereColumn('icd.code_billing', 'billing_notifications.code_billing')
                                  ->whereRaw('YEAR(ic.invoice_date) = YEAR(billing_notifications.notification_date)')
                                  ->whereRaw('MONTH(ic.invoice_date) = MONTH(billing_notifications.notification_date)');
                          });
                    });
            })
            ->with(['company'])
            ->orderBy('billing_notifications.notification_date', 'DESC')
            ->orderBy('billing_notifications.id', 'DESC');

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

        // Map indeks kolom ke field database
        // 0: row_number, 1: notification_code, 2: company, 3: code_billing, 4: total_items, 5: notification_date, 6: invoice_status, 7: message, 8: action
        $filterMap = [
            1 => ['field' => 'id', 'type' => 'having_id'],
            2 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
            3 => ['field' => 'code_billing', 'type' => 'like'],
            4 => ['field' => 'total_items', 'type' => 'having_exact'],
            5 => ['field' => 'notification_date', 'type' => 'date'],
            6 => ['field' => 'invoice_payment_status', 'type' => 'having_like'],
            7 => ['field' => 'message', 'type' => 'like'],
        ];

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);

            if ($searchValue === null || $searchValue === '') continue;

            switch ($config['type']) {
                case 'like':
                    $query->where('billing_notifications.' . $config['field'], 'like', "%{$searchValue}%");
                    break;
                case 'date':
                    $query->whereRaw("DATE_FORMAT(billing_notifications.notification_date, '%d/%m/%Y') LIKE ? OR billing_notifications.notification_date LIKE ?", ["%{$searchValue}%", "%{$searchValue}%"]);
                    break;
                case 'having_id':
                    $query->whereRaw("CONCAT('NOTIF-', LPAD(billing_notifications.id, 5, '0')) LIKE ? OR billing_notifications.id LIKE ?", ["%{$searchValue}%", "%{$searchValue}%"]);
                    break;
                case 'having_like':
                    $query->havingRaw("{$config['field']} LIKE ?", ["%{$searchValue}%"]);
                    break;
                case 'having_exact':
                    $query->havingRaw("{$config['field']} = ?", [(int) $searchValue]);
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
                $join->whereRaw('YEAR(invoice_clients.invoice_date) = YEAR(billing_notifications.notification_date)')
                    ->whereRaw('MONTH(invoice_clients.invoice_date) = MONTH(billing_notifications.notification_date)');
            })
            ->join('invoice_client_details', function ($join) {
                $join->on('invoice_clients.id', '=', 'invoice_client_details.invoice_client_id')
                    ->on('invoice_client_details.code_billing', '=', 'billing_notifications.code_billing');
            })
            ->where('invoice_clients.status', '=', 'Paid')
            ->whereNull('billing_notifications.deleted_at');

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
