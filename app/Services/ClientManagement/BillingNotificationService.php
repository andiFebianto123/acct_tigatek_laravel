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
            $count = 0;

            $reminders = \App\Models\BillingReminder::whereNotNull('start_date')
                ->whereNotNull('period')
                ->get();

            $advanceCycle = function (Carbon $date, string $period): Carbon {
                return match ($period) {
                    '1_month'  => $date->copy()->addMonth(),
                    '3_months' => $date->copy()->addMonths(3),
                    '6_months' => $date->copy()->addMonths(6),
                    '1_year'   => $date->copy()->addYear(),
                    default    => $date->copy()->addMonth(),
                };
            };

            foreach ($reminders as $reminder) {
                if (empty($reminder->code_billing) || empty($reminder->start_date) || empty($reminder->period)) {
                    continue;
                }

                $startDate = Carbon::parse($reminder->start_date)->startOfDay();
                $endDate = !empty($reminder->end_date) ? Carbon::parse($reminder->end_date)->startOfDay() : null;

                // Siklus pertama dihitung dari start_date + periode
                $cycleDate = $advanceCycle($startDate, $reminder->period);

                // Telusuri seluruh siklus yang telah jatuh tempo hingga hari ini (Opsi 1: Akumulasi Notifikasi)
                while ($cycleDate->lte($today) && (!$endDate || $cycleDate->lte($endDate))) {
                    $cycleDateStr = $cycleDate->toDateString();

                    // Cek apakah invoice untuk kode billing pada periode siklus ini sudah dibuat
                    $hasInvoice = $this->invoiceClientRepository->hasInvoiceForCodeBilling($reminder->code_billing, $cycleDateStr);

                    if (!$hasInvoice) {
                        $dueDateFormatted = $cycleDate->format('d/m/Y');
                        $message = "Tagihan Kode Billing {$reminder->code_billing} jatuh tempo pada {$dueDateFormatted}.";

                        BillingNotification::updateOrCreate([
                            'company_id'        => $reminder->company_id,
                            'code_billing'      => $reminder->code_billing,
                            'notification_date' => $cycleDateStr,
                        ], [
                            'message'           => $message,
                        ]);
                        $count++;
                    }

                    // Maju ke siklus berikutnya
                    $cycleDate = $advanceCycle($cycleDate, $reminder->period);
                }

                // Simpan tanggal jatuh tempo siklus mendatang (next_notification_date)
                $nextNotificationDate = (!$endDate || $cycleDate->lte($endDate)) ? $cycleDate->toDateString() : null;
                if ($reminder->next_notification_date !== $nextNotificationDate) {
                    $reminder->update(['next_notification_date' => $nextNotificationDate]);
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
