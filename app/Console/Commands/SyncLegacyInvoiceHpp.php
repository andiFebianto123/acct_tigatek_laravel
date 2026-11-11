<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceClient;
use App\Models\InvoiceClientDetail;
use App\Models\DeliveryNoteDetail;
use App\Models\DeviceStock;
use App\Services\ProfitLost\ProfitLostService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncLegacyInvoiceHpp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fifo:sync-legacy-hpp 
                            {--dry-run : Pratinjau perhitungan tanpa menyimpan perubahan ke database}
                            {--invoice= : ID spesifik Invoice Client yang ingin disinkronkan}
                            {--limit= : Batasi jumlah total invoice yang diproses (misal: --limit=5)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi nilai HPP (cogs_amount_base) untuk data invoice lama dan perbarui Laporan Laba Rugi';

    /**
     * Execute the console command.
     */
    public function handle(ProfitLostService $profitLostService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $invoiceId = $this->option('invoice');
        $limit = $this->option('limit');

        $this->newLine();
        $this->info('========================================================================');
        $this->info('          SINKRONISASI HPP (COGS) DATA INVOICE LAMA / LEGACY           ');
        $this->info('========================================================================');

        if ($isDryRun) {
            $this->warn(' [MODE DRY-RUN AKTIF] Data tidak akan disimpan ke database.');
        } else {
            $this->alert(' [MODE LIVE EXECUTION] Perubahan akan disimpan secara permanen ke database!');
        }

        if ($limit && (int) $limit > 0) {
            $this->line(" [PENGAMAN LIMIT] Dibatasi maksimal: <comment>{$limit} invoice</comment>");
        }
        $this->newLine();

        $query = InvoiceClient::with(['invoice_client_details', 'delivery_note.details']);

        if ($invoiceId) {
            $query->where('id', (int) $invoiceId);
        }

        // Hanya panggil invoice yang memiliki rincian barang dengan nilai cogs_amount_base atau cogs_amount bernilai 0 / null
        $query->whereHas('invoice_client_details', function ($q) {
            $q->whereNotNull('device_stock_id')
              ->where(function ($sub) {
                  $sub->whereNull('cogs_amount_base')
                      ->orWhere('cogs_amount_base', 0)
                      ->orWhereNull('cogs_amount')
                      ->orWhere('cogs_amount', 0);
              });
        });

        if ($limit && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $invoices = $query->orderBy('id', 'asc')->get();

        if ($invoices->isEmpty()) {
            $this->info('Tidak ada data invoice lama yang perlu disinkronkan.');
            return 0;
        }

        $tableRows = [];
        $totalInvoicesUpdated = 0;
        $totalItemsUpdated = 0;
        $totalHppAmount = 0.0;

        foreach ($invoices as $invoice) {
            $invoiceModified = false;
            $exchangeRate = (float) ($invoice->exchange_rate ?? 1.0);
            $currencyCode = $invoice->currency_code ?? 'IDR';

            foreach ($invoice->invoice_client_details as $detail) {
                if (!$detail->device_stock_id) {
                    continue;
                }

                $currentCogsBase = (float) ($detail->cogs_amount_base ?? 0);
                $currentCogs     = (float) ($detail->cogs_amount ?? 0);
                if ($currentCogsBase > 0 && $currentCogs > 0) {
                    // Item ini sudah memiliki nilai HPP yang valid, lewati
                    continue;
                }

                $qty = max(1, (int) ($detail->qty ?? 1));
                $calculatedCogsBase = 0.0;
                $source = '-';

                // 1. Cek dari detail Surat Jalan terkait jika ada
                if ($detail->delivery_note_detail_id) {
                    $dnDetail = DeliveryNoteDetail::find($detail->delivery_note_detail_id);
                    if ($dnDetail && (float) $dnDetail->cogs_amount_base > 0) {
                        $dnQty = max(1, (int) ($dnDetail->qty ?? 1));
                        $unitCogs = (float) $dnDetail->cogs_amount_base / $dnQty;
                        $calculatedCogsBase = $unitCogs * $qty;
                        $source = "Surat Jalan #{$dnDetail->delivery_note_id} (Detail #{$dnDetail->id})";
                    }
                }

                // 2. Jika belum ditemukan, cari dari Surat Jalan invoice secara keseluruhan
                if ($calculatedCogsBase <= 0 && $invoice->delivery_note) {
                    $matchedDnDetail = $invoice->delivery_note->details
                        ->where('device_stock_id', $detail->device_stock_id)
                        ->first();

                    if ($matchedDnDetail && (float) $matchedDnDetail->cogs_amount_base > 0) {
                        $dnQty = max(1, (int) ($matchedDnDetail->qty ?? 1));
                        $unitCogs = (float) $matchedDnDetail->cogs_amount_base / $dnQty;
                        $calculatedCogsBase = $unitCogs * $qty;
                        $source = "Surat Jalan (Matching Stock #{$matchedDnDetail->id})";
                    }
                }

                // 3. Fallback: ambil harga modal dari Master Device Stock
                if ($calculatedCogsBase <= 0) {
                    $masterStock = DeviceStock::find($detail->device_stock_id);
                    if ($masterStock) {
                        $unitPriceBase = (float) ($masterStock->buy_price_base ?? 0);
                        if ($unitPriceBase <= 0 && (float) ($masterStock->buy_price ?? 0) > 0) {
                            $stockRate = (float) ($masterStock->exchange_rate ?? 1.0);
                            $unitPriceBase = (float) $masterStock->buy_price * $stockRate;
                        }

                        if ($unitPriceBase > 0) {
                            $calculatedCogsBase = $qty * $unitPriceBase;
                            $source = "Master Stock ({$masterStock->name} @ Rp " . number_format($unitPriceBase, 0, ',', '.') . ")";
                        }
                    }
                }

                if ($calculatedCogsBase > 0) {
                    $calculatedCogs = ($currencyCode === 'USD' && $exchangeRate > 0)
                        ? round($calculatedCogsBase / $exchangeRate, 4)
                        : round($calculatedCogsBase, 4);

                    $tableRows[] = [
                        'Inv ID'     => $invoice->id,
                        'No Invoice' => $invoice->invoice_number,
                        'Barang'     => $detail->name ?: ('Stock #' . $detail->device_stock_id),
                        'Qty'        => $qty,
                        'HPP Lama'   => 'Rp ' . number_format($currentCogsBase, 0, ',', '.'),
                        'HPP Baru'   => 'Rp ' . number_format($calculatedCogsBase, 0, ',', '.'),
                        'Sumber'     => $source,
                    ];

                    $totalItemsUpdated++;
                    $totalHppAmount += $calculatedCogsBase;
                    $invoiceModified = true;

                    if (!$isDryRun) {
                        $detail->cogs_amount_base = round($calculatedCogsBase, 4);
                        $detail->cogs_amount      = round($calculatedCogs, 4);
                        $detail->save();
                    }
                }
            }

            if ($invoiceModified) {
                $totalInvoicesUpdated++;

                if (!$isDryRun) {
                    try {
                        // Sinkronkan data Laba Rugi untuk invoice ini
                        $profitLostService->syncProfitLostFromInvoice($invoice);
                    } catch (\Throwable $e) {
                        Log::error("Gagal sync Laba Rugi Invoice ID {$invoice->id}: " . $e->getMessage());
                        $this->warn("! Gagal memperbarui Laba Rugi untuk Invoice ID {$invoice->id}: " . $e->getMessage());
                    }
                }
            }
        }

        if (empty($tableRows)) {
            $this->info('Seluruh invoice lama sudah memiliki nilai HPP yang lengkap atau tidak ditemukan data modal master.');
            return 0;
        }

        $this->table(
            ['Inv ID', 'No Invoice', 'Barang', 'Qty', 'HPP Lama', 'HPP Baru', 'Sumber Nilai HPP'],
            $tableRows
        );

        $this->newLine();
        $this->info("Rangkuman Hasil Sinkronisasi:");
        $this->info("- Total Invoice Diperbarui : {$totalInvoicesUpdated}");
        $this->info("- Total Baris Item Diperbaiki: {$totalItemsUpdated}");
        $this->info("- Total Nilai HPP Terbentuk : Rp " . number_format($totalHppAmount, 0, ',', '.'));

        if ($isDryRun) {
            $this->newLine();
            $this->warn("Untuk menerapkan perubahan ini secara permanen ke database, jalankan perintah tanpa opsi --dry-run:");
            $this->line("  <info>php artisan fifo:sync-legacy-hpp</info>");
        } else {
            $this->newLine();
            $this->info("✓ Seluruh data HPP invoice lama dan Laporan Laba Rugi telah berhasil diperbarui!");
        }

        $this->newLine();
        return 0;
    }
}
