<?php

namespace App\Services\ClientManagement;

use App\DTOs\ClientManagement\BillingReminderSaveData;
use App\Models\BillingDevice;
use App\Models\BillingReminder;
use App\Models\BillingSimcard;
use App\Repositories\ClientManagement\BillingReminderRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingReminderService
{
    protected BillingReminderRepository $repository;

    public function __construct(BillingReminderRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Calculate next notification date based on start_date, period, and end_date.
     */
    public function calculateNextNotificationDate(?string $startDate, ?string $period, ?string $endDate = null): ?string
    {
        if (empty($startDate) || empty($period)) {
            return null;
        }

        try {
            $start = Carbon::parse($startDate);
            $end = !empty($endDate) ? Carbon::parse($endDate) : null;
            $today = Carbon::today();

            $next = match ($period) {
                '1_month'  => $start->copy()->addMonth(),
                '3_months' => $start->copy()->addMonths(3),
                '6_months' => $start->copy()->addMonths(6),
                '1_year'   => $start->copy()->addYear(),
                default    => $start->copy()->addMonth(),
            };

            // If next calculated date is already past today and end date allows, advance to next cycle
            if ($next->lt($today) && $end) {
                while ($next->lt($today) && $next->lte($end)) {
                    $next = match ($period) {
                        '1_month'  => $next->copy()->addMonth(),
                        '3_months' => $next->copy()->addMonths(3),
                        '6_months' => $next->copy()->addMonths(6),
                        '1_year'   => $next->copy()->addYear(),
                        default    => $next->copy()->addMonth(),
                    };
                }
            }

            return $next->toDateString();
        } catch (\Exception $e) {
            Log::warning("BillingReminderService::calculateNextNotificationDate failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Save or update billing reminder setting for a code_billing.
     */
    public function saveReminder(BillingReminderSaveData $data): BillingReminder
    {
        return DB::transaction(function () use ($data) {
            $nextNotificationDate = $this->calculateNextNotificationDate(
                $data->start_date,
                $data->period,
                $data->end_date
            );

            // Fetch aggregate installation_date if not explicitly provided
            $installationDate = $data->installation_date;
            if (empty($installationDate)) {
                $agg = $this->repository->getAggregateForCodeBilling($data->code_billing, $data->company_id);
                $installationDate = $agg->installation_date ?? null;
            }

            $reminder = BillingReminder::updateOrCreate(
                [
                    'company_id'   => $data->company_id,
                    'code_billing' => $data->code_billing,
                ],
                [
                    'installation_date'      => $installationDate,
                    'start_date'             => $data->start_date,
                    'end_date'               => $data->end_date,
                    'period'                 => $data->period,
                    'next_notification_date' => $nextNotificationDate,
                ]
            );

            // Synchronize expired_date on all devices and simcards under this code_billing
            if (!empty($data->end_date)) {
                BillingDevice::where('code_billing', $data->code_billing)
                    ->when($data->company_id, fn($q) => $q->where('company_id', $data->company_id))
                    ->update(['expired_date' => $data->end_date]);

                BillingSimcard::where('code_billing', $data->code_billing)
                    ->when($data->company_id, fn($q) => $q->where('company_id', $data->company_id))
                    ->update(['expired_date' => $data->end_date]);
            }

            return $reminder;
        });
    }
}
