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
            ->select('billing_notifications.*')
            ->selectSub(function ($sub) {
                $sub->selectRaw('1')
                    ->from('invoice_clients')
                    ->join('invoice_client_details', 'invoice_clients.id', '=', 'invoice_client_details.invoice_client_id')
                    ->whereColumn('invoice_clients.type_device', 'billing_notifications.billable_type')
                    ->whereRaw('YEAR(invoice_clients.invoice_date) = YEAR(billing_notifications.notification_date)')
                    ->whereRaw('MONTH(invoice_clients.invoice_date) = MONTH(billing_notifications.notification_date)')
                    ->whereColumn('invoice_client_details.name', \Illuminate\Support\Facades\DB::raw("
                        (CASE 
                            WHEN billing_notifications.billable_type = 'App\\\\Models\\\\BillingDevice' THEN (
                                SELECT device_id FROM billing_devices WHERE billing_devices.id = billing_notifications.billable_id LIMIT 1
                            )
                            WHEN billing_notifications.billable_type = 'App\\\\Models\\\\BillingSimcard' THEN (
                                SELECT device_profile_id FROM billing_simcards WHERE billing_simcards.id = billing_notifications.billable_id LIMIT 1
                            )
                        END)
                    "))
                    ->limit(1);
            }, 'has_invoice_this_month')
            ->with(['company', 'billable']);

        // Scoping based on company
        if ($filters->company_id !== null && $filters->company_id !== '') {
            $query->where('company_id', $filters->company_id);
        } else if (backpack_user() && !backpack_user()->hasRole('Super Admin')) {
            $query->where('company_id', backpack_user()->company_id);
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

        $isSuperAdmin = backpack_user() && backpack_user()->hasRole('Super Admin');

        if ($isSuperAdmin) {
            $filterMap = [
                1 => ['field' => 'company.name', 'type' => 'relation', 'relation' => 'company'],
                2 => ['field' => 'billable_type', 'type' => 'like'],
                3 => ['field' => 'billable_id', 'type' => 'like'],
                4 => ['field' => 'notification_date', 'type' => 'like'],
                5 => ['field' => 'message', 'type' => 'like'],
            ];
        } else {
            $filterMap = [
                1 => ['field' => 'billable_type', 'type' => 'like'],
                2 => ['field' => 'billable_id', 'type' => 'like'],
                3 => ['field' => 'notification_date', 'type' => 'like'],
                4 => ['field' => 'message', 'type' => 'like'],
            ];
        }

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);

            if ($searchValue === null || $searchValue === '') continue;

            switch ($config['type']) {
                case 'like':
                    if ($config['field'] === 'billable_id') {
                        $query->where(function ($q) use ($searchValue) {
                            $q->whereHasMorph('billable', [\App\Models\BillingDevice::class, \App\Models\BillingSimcard::class], function ($subQuery, $type) use ($searchValue) {
                                if ($type === \App\Models\BillingDevice::class) {
                                    $subQuery->where('device_id', 'like', "%{$searchValue}%");
                                } elseif ($type === \App\Models\BillingSimcard::class) {
                                    $subQuery->where('msisdn', 'like', "%{$searchValue}%");
                                }
                            });
                        });
                    } else {
                        $query->where($config['field'], 'like', "%{$searchValue}%");
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
}
