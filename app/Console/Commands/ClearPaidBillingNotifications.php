<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ClientManagement\BillingNotificationService;
use Illuminate\Support\Facades\Log;

class ClearPaidBillingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:clear-paid-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan and soft delete billing notifications that have already been paid';

    /**
     * Execute the console command.
     */
    public function handle(BillingNotificationService $service)
    {
        $this->info('Starting to clear paid billing notifications...');
        try {
            $count = $service->clearPaidNotifications();
            $this->info("Successfully cleared {$count} paid billing notifications.");
            Log::info("ClearPaidBillingNotifications executed: {$count} notifications cleared.");
        } catch (\Throwable $e) {
            $this->error('Failed to clear paid billing notifications: ' . $e->getMessage());
            Log::error('ClearPaidBillingNotifications failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
}
