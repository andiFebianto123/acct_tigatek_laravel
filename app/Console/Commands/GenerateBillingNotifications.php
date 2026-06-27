<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ClientManagement\BillingNotificationService;
use Illuminate\Support\Facades\Log;

class GenerateBillingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan billing records (devices and simcards) and generate billing notifications';

    /**
     * Execute the console command.
     */
    public function handle(BillingNotificationService $service)
    {
        $this->info('Starting billing notification generation...');
        try {
            $count = $service->generateNotifications();
            $this->info("Successfully generated/updated {$count} billing notifications.");
            Log::info("GenerateBillingNotifications executed: {$count} notifications generated.");
        } catch (\Throwable $e) {
            $this->error('Failed to generate billing notifications: ' . $e->getMessage());
            Log::error('GenerateBillingNotifications failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
}
