<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceClient;
use App\Services\ProfitLost\ProfitLostService;
use Illuminate\Support\Facades\Log;

class SyncExistingInvoicesToProfitLost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profit-lost:sync-existing-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi seluruh invoice lama ke menu Laba Rugi (Penjualan & Proyek)';

    /**
     * Execute the console command.
     */
    public function handle(ProfitLostService $service)
    {
        $this->info('Memulai sinkronisasi invoice lama ke Laba Rugi...');

        $invoices = InvoiceClient::all();
        $total = $invoices->count();

        if ($total === 0) {
            $this->warn('Tidak ada data invoice ditemukan.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $syncedCount = 0;
        $failedCount = 0;

        foreach ($invoices as $invoice) {
            try {
                $result = $service->syncProfitLostFromInvoice($invoice);
                if ($result) {
                    $syncedCount++;
                }
            } catch (\Throwable $e) {
                $failedCount++;
                Log::error("Gagal sinkronisasi invoice ID {$invoice->id} ke Laba Rugi: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sinkronisasi selesai!");
        $this->info("- Total invoice diproses : {$total}");
        $this->info("- Berhasil disinkronkan  : {$syncedCount}");
        if ($failedCount > 0) {
            $this->warn("- Gagal disinkronkan     : {$failedCount} (cek log untuk detail)");
        }

        return 0;
    }
}
