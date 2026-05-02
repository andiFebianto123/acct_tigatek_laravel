<?php

namespace App\Console\Commands;

use App\Http\Helpers\CustomVoid;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\CastAccount;
use App\Models\InvoiceClient;
use App\Models\LogPayment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ImportExcelInvoiceTransactions extends Command
{
    /**
     * Nama dan signature perintah terminal.
     */
    protected $signature = 'import:invoice-transactions {path : Path ke file excel}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Import transaksi kas dari Excel dengan pencarian Akun Kas dan Invoice otomatis';

    /**
     * Jalankan perintah.
     */
    public function handle()
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File tidak ditemukan di path: {$path}");
            return 1;
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
            $this->info("Ditemukan " . count($rows) . " baris data. Memulai proses...");

            $bar = $this->output->createProgressBar(count($rows));
            $bar->start();

            $successCount = 0;
            $failCount = 0;

            foreach ($rows as $index => $row) {
                $kodeAkunKas = $row['kode_akun'] ?? null;
                $tanggal     = $row['tanggal']   ?? null;
                $nominal     = $row['nominal']   ?? null;
                $keterangan  = $row['keterangan'] ?? null;
                $status      = strtolower($row['status'] ?? 'enter');
                $noInvoice   = $row['no_invoice'] ?? null;
                $jobName     = $row['job_name'] ?? null;


                if (!$kodeAkunKas || !$nominal) {
                    $bar->advance();
                    continue;
                }

                DB::beginTransaction();
                try {


                    $account = Account::where('code', $kodeAkunKas)->first();
                    if (!$account) {
                        throw new \Exception("Akun '{$kodeAkunKas}' tidak ditemukan di tabel accounts.");
                    }

                    $castAccount = CastAccount::where('account_id', $account->id)->first();
                    if (!$castAccount) {
                        throw new \Exception("Akun '{$kodeAkunKas}' tidak terdaftar sebagai Akun Kas (Cast Account).");
                    }

                    $invoiceId = null;
                    if ($noInvoice) {
                        $invoice = InvoiceClient::where('invoice_number', $noInvoice)
                            ->first();

                        if ($invoice) {
                            $invoiceId = $invoice->id;
                        } else {
                            $this->warn("\nWarning baris " . ($index + 2) . ": Nomor Invoice '{$noInvoice}' tidak ditemukan.");
                        }
                    }

                    // Transaksi wajib memiliki invoice yang valid
                    if (!$invoiceId) {
                        throw new \Exception("Transaksi dibatalkan karena Invoice tidak ditemukan atau kosong (pencarian: '{$noInvoice}').");
                    }

                    $transaction = AccountTransaction::where("reference_type", "App\\Models\\InvoiceClient")
                        ->where('reference_id', $invoiceId)->first();

                    if ($transaction) {
                        CustomVoid::rollbackPayment(AccountTransaction::class, $transaction->id);
                    }

                    $log_payment_invoice_exists = LogPayment::whereHasMorph('reference', InvoiceClient::class, function ($q) use ($invoiceId) {
                        $q->where('id', $invoiceId);
                    })->exists();

                    if ($log_payment_invoice_exists) {
                        CustomVoid::rollbackPayment(InvoiceClient::class, $invoiceId, 'CREATE_PAYMENT_INVOICE');
                    }

                    $mockRequest = new Request([
                        'cast_account_id'     => $castAccount->id,
                        'date_transaction'    => $tanggal ? Carbon::parse($tanggal)->format('Y-m-d') : date('Y-m-d'),
                        'nominal_transaction' => $nominal,
                        'description'         => $keterangan ?? "Import via Command Terminal",
                        'no_invoice'          => $invoiceId,
                        'job_name'            => $jobName,
                    ]);

                    CustomVoid::storeTransaction($mockRequest, $status);

                    DB::commit();
                    $successCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("\nError di baris " . ($index + 2) . " ({$kodeAkunKas}): " . $e->getMessage());
                    $failCount++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Import selesai! Berhasil: {$successCount}, Gagal: {$failCount}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan fatal: " . $e->getMessage());
            return 1;
        }
    }
}
