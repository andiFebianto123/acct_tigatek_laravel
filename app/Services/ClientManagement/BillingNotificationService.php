<?php

namespace App\Services\ClientManagement;

use App\Models\BillingDevice;
use App\Models\BillingSimcard;
use App\Models\BillingNotification;
use App\Repositories\Invoice\InvoiceClientRepository;
use App\Repositories\ClientManagement\BillingNotificationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingNotificationService
{
    private InvoiceClientRepository $invoiceClientRepository;
    private BillingNotificationRepository $billingNotificationRepository;

    public function __construct(
        InvoiceClientRepository $invoiceClientRepository,
        BillingNotificationRepository $billingNotificationRepository
    ) {
        $this->invoiceClientRepository = $invoiceClientRepository;
        $this->billingNotificationRepository = $billingNotificationRepository;
    }

    /**
     * Scan Billing Devices and SIM Cards to generate notifications based on expired_date and reminder_date.
     */
    public function generateNotifications(): int
    {
        return DB::transaction(function () {
            $today = Carbon::today();
            $targetH7 = Carbon::today()->addDays(7);
            $count = 0;

            // Process active reminders by code_billing
            $reminders = \App\Models\BillingReminder::whereNotNull('next_notification_date')->get();

            foreach ($reminders as $reminder) {
                if (empty($reminder->code_billing)) {
                    continue;
                }

                $nextDate = Carbon::parse($reminder->next_notification_date)->startOfDay();
                $endDate = !empty($reminder->end_date) ? Carbon::parse($reminder->end_date)->startOfDay() : null;

                // Check if current date reached next_notification_date or within target window (H-7)
                $shouldNotify = false;
                if (($today->gte($nextDate) || $targetH7->gte($nextDate)) && (!$endDate || $today->lte($endDate))) {
                    $shouldNotify = true;
                }

                if ($shouldNotify) {
                    $checkDate = $reminder->next_notification_date->toDateString();

                    // Prevent duplicate if invoice already exists for this code_billing
                    if ($this->invoiceClientRepository->hasInvoiceForCodeBilling($reminder->code_billing, $checkDate)) {
                        $shouldNotify = false;
                    }
                }

                if ($shouldNotify) {
                    $dueDateFormatted = Carbon::parse($reminder->next_notification_date)->format('d/m/Y');
                    $message = "Tagihan Kode Billing {$reminder->code_billing} jatuh tempo pada {$dueDateFormatted}.";

                    BillingNotification::updateOrCreate([
                        'company_id'        => $reminder->company_id,
                        'code_billing'      => $reminder->code_billing,
                        'notification_date' => $today,
                    ], [
                        'message'           => $message,
                    ]);
                    $count++;
                }
            }

            return $count;
        });
    }

    /**
     * Clear (soft delete) all billing notifications that have already been paid.
     */
    public function clearPaidNotifications(): int
    {
        return DB::transaction(function () {
            $totalDeleted = 0;

            do {
                $ids = $this->billingNotificationRepository->getPaidNotificationIds(1000);
                if (!empty($ids)) {
                    $deleted = BillingNotification::whereIn('id', $ids)->delete();
                    $totalDeleted += $deleted;
                }
            } while (!empty($ids));

            return $totalDeleted;
        });
    }
}
