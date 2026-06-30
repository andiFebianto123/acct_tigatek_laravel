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

            // 1. Process Billing Devices
            $devices = BillingDevice::all();
            foreach ($devices as $device) {
                $shouldNotify = false;
                $checkDate = null;

                if (empty($device->reminder_date)) {
                    // Default check: H+7 from today
                    $shouldNotify = true;
                    $checkDate = $targetH7->toDateString();
                } else {
                    // Reminder check: reminder_date falls on today
                    if (Carbon::parse($device->reminder_date)->startOfDay()->equalTo($today)) {
                        $shouldNotify = true;
                        $checkDate = Carbon::parse($device->reminder_date)->toDateString();
                    }
                }

                if ($shouldNotify && $checkDate) {
                    if ($device->device_id && $this->invoiceClientRepository->hasInvoiceForDevice(BillingDevice::class, $device->device_id, $checkDate)) {
                        $shouldNotify = false;
                    }
                }

                if ($shouldNotify) {
                    $expiredStr = $device->expired_date ? Carbon::parse($device->expired_date)->format('d/m/Y') : '-';
                    $message = "Tagihan Device ID {$device->device_id} (" . ($device->vehicle_name ?? 'Tanpa Nama') . ") jatuh tempo pada {$expiredStr}.";

                    BillingNotification::updateOrCreate([
                        'billable_type' => BillingDevice::class,
                        'billable_id' => $device->id,
                        'notification_date' => $today,
                    ], [
                        'company_id' => $device->company_id,
                        'message' => $message,
                    ]);
                    $count++;
                }
            }

            // 2. Process Billing SIM Cards
            $simcards = BillingSimcard::all();
            foreach ($simcards as $simcard) {
                $shouldNotify = false;
                $checkDate = null;

                if (empty($simcard->reminder_date)) {
                    // Default check: H+7 from today
                    $shouldNotify = true;
                    $checkDate = $targetH7->toDateString();
                } else {
                    // Reminder check: reminder_date falls on today
                    if (Carbon::parse($simcard->reminder_date)->startOfDay()->equalTo($today)) {
                        $shouldNotify = true;
                        $checkDate = Carbon::parse($simcard->reminder_date)->toDateString();
                    }
                }

                if ($shouldNotify && $checkDate) {
                    if ($simcard->device_profile_id && $this->invoiceClientRepository->hasInvoiceForDevice(BillingSimcard::class, $simcard->device_profile_id, $checkDate)) {
                        $shouldNotify = false;
                    }
                }

                if ($shouldNotify) {
                    $expiredStr = $simcard->expired_date ? Carbon::parse($simcard->expired_date)->format('d/m/Y') : '-';
                    $message = "Tagihan SIMCARD MSISDN {$simcard->msisdn} (ICCID: " . ($simcard->iccid ?? '-') . ") jatuh tempo pada {$expiredStr}.";

                    BillingNotification::updateOrCreate([
                        'billable_type' => BillingSimcard::class,
                        'billable_id' => $simcard->id,
                        'notification_date' => $today,
                    ], [
                        'company_id' => $simcard->company_id,
                        'message' => $message,
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
