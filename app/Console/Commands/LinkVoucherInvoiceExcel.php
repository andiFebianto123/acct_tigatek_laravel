<?php

namespace App\Console\Commands;

use App\Models\InvoiceClient;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class LinkVoucherInvoiceExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voucher:link-invoice-excel
                            {path : Path ke file Excel (.xlsx / .xls / .csv)}
                            {--dry-run : Jalankan simulasi pengecekan tanpa menyimpan perubahan ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghubungkan data voucher existing ke invoice (mengisi kolom vouchers.invoice_client_id) berdasarkan file Excel';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = $this->argument('path');
        $isDryRun = (bool) $this->option('dry-run');

        if (!file_exists($path)) {
            $this->error("File tidak ditemukan di path: {$path}");
            return 1;
        }

        if ($isDryRun) {
            $this->warn("=== MODE DRY-RUN AKTIF: Perubahan TIDAK akan disimpan ke database ===");
        }

        $this->info("Membaca file Excel...");

        try {
            $import = new class implements ToArray, WithHeadingRow {
                public function array(array $array)
                {
                    return $array;
                }
            };

            $data = Excel::toArray($import, $path);

            if (empty($data) || empty($data[0])) {
                $this->error("File Excel kosong atau tidak memiliki data.");
                return 1;
            }

            $rows = $data[0];
            $totalRows = count($rows);
            $this->info("Ditemukan {$totalRows} baris data. Memulai pencocokan data...");

            $bar = $this->output->createProgressBar($totalRows);
            $bar->start();

            $updatedCount = 0;
            $alreadyLinkedCount = 0;
            $voucherNotFoundCount = 0;
            $invoiceNotFoundCount = 0;
            $emptyRowCount = 0;
            $failedDetails = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +1 untuk 1-based, +1 untuk header

                // Normalisasi nama kolom menjadi lowercase & trim
                $normalizedRow = [];
                foreach ($row as $k => $v) {
                    $normalizedKey = strtolower(trim((string) $k));
                    $normalizedRow[$normalizedKey] = is_string($v) ? trim($v) : $v;
                }

                // Ambil kode voucher & invoice dengan berbagai variasi nama header
                $kodeVoucher = $normalizedRow['kode_voucher']
                    ?? $normalizedRow['no_voucher']
                    ?? $normalizedRow['nomor_voucher']
                    ?? $normalizedRow['voucher']
                    ?? null;

                $kodeInvoice = $normalizedRow['kode_invoice']
                    ?? $normalizedRow['no_invoice']
                    ?? $normalizedRow['nomor_invoice']
                    ?? $normalizedRow['invoice']
                    ?? null;

                $kodeVoucher = !empty($kodeVoucher) ? trim((string) $kodeVoucher) : null;
                $kodeInvoice = !empty($kodeInvoice) ? trim((string) $kodeInvoice) : null;

                if (empty($kodeVoucher) && empty($kodeInvoice)) {
                    $emptyRowCount++;
                    $bar->advance();
                    continue;
                }

                if (empty($kodeVoucher)) {
                    $voucherNotFoundCount++;
                    $failedDetails[] = [
                        'row' => $rowNumber,
                        'voucher' => '-',
                        'invoice' => $kodeInvoice ?? '-',
                        'reason' => 'Kolom kode voucher kosong',
                    ];
                    $bar->advance();
                    continue;
                }

                if (empty($kodeInvoice)) {
                    $invoiceNotFoundCount++;
                    $failedDetails[] = [
                        'row' => $rowNumber,
                        'voucher' => $kodeVoucher,
                        'invoice' => '-',
                        'reason' => 'Kolom kode invoice kosong',
                    ];
                    $bar->advance();
                    continue;
                }

                // Cari voucher berdasarkan no_voucher
                $voucher = Voucher::where('no_voucher', $kodeVoucher)->first();
                if (!$voucher) {
                    $voucherNotFoundCount++;
                    $failedDetails[] = [
                        'row' => $rowNumber,
                        'voucher' => $kodeVoucher,
                        'invoice' => $kodeInvoice,
                        'reason' => 'Voucher tidak ditemukan di database',
                    ];
                    $bar->advance();
                    continue;
                }

                // Cari invoice berdasarkan invoice_number atau kdp
                $invoice = InvoiceClient::where('invoice_number', $kodeInvoice)
                    ->orWhere('kdp', $kodeInvoice)
                    ->first();

                if (!$invoice) {
                    $invoiceNotFoundCount++;
                    $failedDetails[] = [
                        'row' => $rowNumber,
                        'voucher' => $kodeVoucher,
                        'invoice' => $kodeInvoice,
                        'reason' => 'Invoice tidak ditemukan di database',
                    ];
                    $bar->advance();
                    continue;
                }

                // Cek apakah sudah terhubung ke invoice yang sama
                if ((int) $voucher->invoice_client_id === (int) $invoice->id) {
                    $alreadyLinkedCount++;
                    $bar->advance();
                    continue;
                }

                // HANYA update kolom invoice_client_id
                if (!$isDryRun) {
                    Voucher::where('id', $voucher->id)->update([
                        'invoice_client_id' => $invoice->id,
                    ]);
                }

                $updatedCount++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Tampilkan Ringkasan
            $this->info("=== RINGKASAN HASIL PROSES ===");
            $this->table(
                ['Kategori', 'Jumlah'],
                [
                    ['Total Baris Dibaca', $totalRows],
                    [$isDryRun ? 'Siap Dihubungkan (Valid)' : 'Berhasil Dihubungkan', $updatedCount],
                    ['Sudah Terhubung Sebelumnya', $alreadyLinkedCount],
                    ['Voucher Tidak Ditemukan / Kosong', $voucherNotFoundCount],
                    ['Invoice Tidak Ditemukan / Kosong', $invoiceNotFoundCount],
                    ['Baris Kosong Diabaikan', $emptyRowCount],
                ]
            );

            // Jika ada baris gagal, tampilkan contohnya (maksimal 20 baris pertama)
            if (!empty($failedDetails)) {
                $this->newLine();
                $this->warn("Perhatian: Ditemukan " . count($failedDetails) . " baris yang tidak dapat diproses:");
                $sampleErrors = array_slice($failedDetails, 0, 20);
                $this->table(['Baris Excel', 'Kode Voucher', 'Kode Invoice', 'Keterangan'], $sampleErrors);

                if (count($failedDetails) > 20) {
                    $this->line("... dan " . (count($failedDetails) - 20) . " baris lainnya.");
                }
            }

            if ($isDryRun) {
                $this->newLine();
                $this->info("Simulasi selesai. Jalankan perintah tanpa opsi --dry-run untuk menyimpan perubahan ke database.");
            } else {
                $this->newLine();
                $this->info("Proses sinkronisasi kolom invoice_client_id selesai dengan sukses!");
            }

            return 0;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("Terjadi kesalahan saat memproses file Excel: " . $e->getMessage());
            return 1;
        }
    }
}
