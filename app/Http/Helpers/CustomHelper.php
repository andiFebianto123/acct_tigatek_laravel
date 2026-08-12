<?php

namespace App\Http\Helpers;

use Carbon\Carbon;
use App\Models\Spk;
use App\Models\Asset;
use App\Models\Account;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\ClientPo;
use App\Models\LogPayment;
use App\Models\CastAccount;
use App\Models\SetupClient;
use App\Models\JournalEntry;
use App\Models\InvoiceClient;
use App\Models\PurchaseOrder;
use App\Models\ProjectProfitLost;
use App\Models\ConsolidateIncomeItem;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;

class CustomHelper
{

    public static $settings;

    public static function getAccountMapping($key = null): ?string
    {
        $accountMapping = [
            // Assets & Accounts Receivable
            'RECEIVABLES_INVOICE' => '10201',

            // Liabilities & Taxes
            'DEBT_VOUCHER' => '20101',
            'PO_VENDOR' => '20102',
            'DEBT_PPN' => '20301',
            'UNIFICATION' => '20304',

            // Revenue
            'REVENUE_INVOICE' => '40101',

            // Expenses & WIP
            'WITHOUT_PO' => '50222',
            'PPH_21' => '50301',
            'TAX' => '50303',
            'PPH_23' => '50306',
            'PPH_OMZET' => '50306',
            'PPH_4' => '50307',
            'WIP_OLD' => '50401', // pekerjaan dalam proses
            'WIP' => '10601',

            'NON_WAPU' => '10201',
            'WAPU' => '10202',

            // Equity / Special
            'PROFIT_CONSOLIDATION' => '303',
        ];

        if (isset($accountMapping[$key])) {
            return $accountMapping[$key];
        }

        return null;
    }

    public static function merge_query_params($new_params = [])
    {
        $current_params = request()->all();
        $merged_params = array_merge($current_params, $new_params);
        return '?' . http_build_query($merged_params);
    }

    static function init()
    {
        self::$settings = Setting::first();
    }

    public static function getBanks()
    {
        return \App\Models\Bank::orderBy('name', 'ASC')->pluck('name', 'name')->toArray();
    }

    public static function getYearOptions($table = 'purchase_orders', $column = 'date_po')
    {
        $years = DB::table($table)->select(DB::raw("YEAR($column) as year"))
            ->whereNotNull($column)
            ->distinct()->get();
        $results = [];

        foreach ($years as $y) {
            $results[] = $y->year;
        }
        sort($results);
        return $results;
    }

    public static function getYearOptionsSpk()
    {
        $spk = Spk::select(DB::raw("
            YEAR(date_spk) as year
        "))->distinct()->get();
        $results = [];
        foreach ($spk as $po) {
            $results[] = $po->year;
        }
        return $results;
    }

    public static function getYearOptionsClient()
    {
        $dataset = ClientPo::select(DB::raw("YEAR(end_date) as year"))
            ->distinct()->get();

        $results = [];

        foreach ($dataset as $po) {
            $results[] = $po->year;
        }
        return $results;
    }

    public static function getYearOptionsAsset()
    {
        $dataset = Asset::select(DB::raw("YEAR(year_acquisition) as year"))
            ->distinct()->get();
        $results = [];

        foreach ($dataset as $po) {
            $results[] = $po->year;
        }
        return $results;
    }

    public static function getOptionProject()
    {
        $dataset = Project::select(DB::raw("category"))
            ->distinct()->get();
        $results = [];

        foreach ($dataset as $po) {
            $results[] = $po->category;
        }
        return $results;
    }

    public static function getOptionProjectClient()
    {
        $dataset = SetupClient::select(DB::raw("id, name"))
            ->distinct()->get();
        $results = [];

        foreach ($dataset as $po) {
            $results[] = [
                'id' => $po->id,
                'text' => $po->name,
            ];
        }
        return $results;
    }

    public static function getOptionStatusProject()
    {
        return \App\Models\SetupStatusProject::orderBy('name', 'ASC')->get(['id', 'name'])->toArray();
    }

    public static function getPaidOptions()
    {
        return [
            'Paid',
            'Unpaid',
        ];
    }

    public static function formatRupiah($number, $decimal_digits = 0)
    {
        if ($number < 0 && $number > -1) {
            $number = 0;
        }

        $is_negative = $number < 0;

        $absolute = abs($number);

        $formatted = number_format(
            $absolute,
            $decimal_digits,
            ',',
            '.'
        );

        return $is_negative ? '-' . $formatted : $formatted;
    }

    public static function formatRupiahExcel($number, $decimal_digits = 0)
    {
        if ($number < 0 && $number > -1) {
            $number = 0;
        }

        $is_negative = $number < 0;

        $absolute = abs($number);

        $formatted = number_format(
            $absolute,
            $decimal_digits,
            '',
            ''
        );

        return $is_negative ? '-' . $formatted : $formatted;
    }

    public static function formatRupiahWithCurrency($number, $decimal_digits = 0)
    {
        self::init();

        if ($number < 0 && $number > -1) {
            $number = 0;
        }

        $is_negative = $number < 0;

        $absolute = abs($number);

        $formatted = number_format(
            $absolute,
            $decimal_digits,
            ',',
            '.'
        );

        $nominal = $is_negative ? '-' . $formatted : $formatted;
        return (self::$settings?->currency_symbol) ? self::$settings->currency_symbol . ' ' . $nominal : 'Rp.' . $nominal;
    }

    /**
     * Format mata uang universal (IDR, USD, EUR, dsb.)
     *
     * @param float|int|null $amount
     * @param string|null $currencyCode
     * @param bool $isExport
     * @return string|float
     */
    public static function formatCurrency($amount, ?string $currencyCode = 'IDR', bool $isExport = false)
    {
        $numericAmount = (float) ($amount ?? 0);

        if ($isExport) {
            return $numericAmount;
        }

        $currency = strtoupper($currencyCode ?? 'IDR');

        if ($currency === 'USD') {
            return '$ ' . number_format($numericAmount, 2, '.', ',');
        } elseif ($currency === 'EUR') {
            return '€ ' . number_format($numericAmount, 2, ',', '.');
        }

        // Default IDR
        return 'Rp ' . number_format($numericAmount, 0, ',', '.');
    }

    // update or create journal_entry
    public static function updateOrCreateJournalEntry($payload, $reference)
    {
        if (!isset($payload['currency_code'])) {
            $payload['currency_code'] = 'IDR';
        }
        if (!isset($payload['exchange_rate'])) {
            $payload['exchange_rate'] = 1.0;
        }
        $rate = (float) ($payload['exchange_rate'] ?? 1.0);
        if (!isset($payload['debit_base'])) {
            $payload['debit_base'] = (float) ($payload['debit'] ?? 0) * $rate;
        }
        if (!isset($payload['credit_base'])) {
            $payload['credit_base'] = (float) ($payload['credit'] ?? 0) * $rate;
        }

        $journal = JournalEntry::class;
        $journal::updateOrCreate($reference, $payload);
        return $journal::where($reference)->first();
    }

    public static function insertJournalEntry($payload)
    {
        if (!isset($payload['currency_code'])) {
            $payload['currency_code'] = 'IDR';
        }
        if (!isset($payload['exchange_rate'])) {
            $payload['exchange_rate'] = 1.0;
        }
        $rate = (float) ($payload['exchange_rate'] ?? 1.0);
        if (!isset($payload['debit_base'])) {
            $payload['debit_base'] = (float) ($payload['debit'] ?? 0) * $rate;
        }
        if (!isset($payload['credit_base'])) {
            $payload['credit_base'] = (float) ($payload['credit'] ?? 0) * $rate;
        }

        return JournalEntry::create($payload);
    }

    public static function deleteJournalEntry($reference)
    {
        return JournalEntry::where($reference)->delete();
    }

    public static function total_balance_cast_account($id, $status, $year = null)
    {
        if ($status == CastAccount::CASH) {
            $listCashAccountsQuery = CastAccount::leftJoin('account_transactions', function ($q) use ($id) {
                // $q->on('account_transactions.cast_account_destination_id', '=', 'cast_accounts.id')
                $q->on('account_transactions.cast_account_id', '=', 'cast_accounts.id');
            })
                ->where('cast_accounts.status', CastAccount::CASH)
                ->where('cast_accounts.id', $id);

            if ($year && $year != 'all') {
                $listCashAccountsQuery->whereYear('account_transactions.date_transaction', $year);
            }

            $listCashAccounts = $listCashAccountsQuery->groupBy('cast_accounts.id')
                ->orderBy('cast_accounts.id', 'ASC')
                ->select(DB::raw("
                cast_accounts.id,
                SUM(IF(account_transactions.status = 'enter', IF(cast_accounts.currency_code = 'USD', account_transactions.nominal_transaction, IFNULL(account_transactions.nominal_transaction_base, account_transactions.nominal_transaction)), 0)) as total_saldo_enter,
                SUM(IF(account_transactions.status = 'out', IF(cast_accounts.currency_code = 'USD', account_transactions.nominal_transaction, IFNULL(account_transactions.nominal_transaction_base, account_transactions.nominal_transaction)), 0)) as total_saldo_out
            "))
                ->get();

            if ($listCashAccounts) {
                foreach ($listCashAccounts as $cash) {
                    if ($cash->id == $id) {
                        return ($cash->total_saldo_enter - $cash->total_saldo_out);
                    }
                }
            }
        } else if ($status == CastAccount::LOAN) {
            $castAccount = CastAccount::find($id);
            $isUsd = ($castAccount && $castAccount->currency_code === 'USD');

            $journal_query = JournalEntry::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id, $year) {
                $q->where('cast_account_id', $id);
                if ($year && $year != 'all') {
                    $q->whereYear('date_transaction', $year);
                }
            })->orWhereHasMorph('reference', CastAccount::class, function ($q) use ($id) {
                $q->where('id', $id);
            });

            if ($isUsd) {
                $journal_ = $journal_query
                    ->select(DB::raw('SUM(debit) - SUM(credit) as total'))
                    ->get();
            } else {
                $journal_ = $journal_query
                    ->select(DB::raw('SUM(IFNULL(debit_base, debit)) - SUM(IFNULL(credit_base, credit)) as total'))
                    ->get();
            }
            if ($journal_) {
                foreach ($journal_ as $journal) {
                    return $journal->total;
                }
            }
        }
    }

    public static function total_balance_cast_account_edit($id_cast_account, $id_transaction, $status)
    {
        if ($status == CastAccount::CASH) {
            $listCashAccounts = CastAccount::leftJoin('account_transactions', function ($q) use ($id_cast_account) {
                // $q->on('account_transactions.cast_account_destination_id', '=', 'cast_accounts.id')
                $q->on('account_transactions.cast_account_id', '=', 'cast_accounts.id');
            })
                ->where('cast_accounts.status', CastAccount::CASH)
                ->where('cast_accounts.id', $id_cast_account)
                ->where('account_transactions.id', '!=', $id_transaction)
                ->groupBy('cast_accounts.id')
                ->orderBy('cast_accounts.id', 'ASC')
                ->select(DB::raw("
                cast_accounts.id,
                SUM(IF(account_transactions.status = 'enter', IF(cast_accounts.currency_code = 'USD', account_transactions.nominal_transaction, IFNULL(account_transactions.nominal_transaction_base, account_transactions.nominal_transaction)), 0)) as total_saldo_enter,
                SUM(IF(account_transactions.status = 'out', IF(cast_accounts.currency_code = 'USD', account_transactions.nominal_transaction, IFNULL(account_transactions.nominal_transaction_base, account_transactions.nominal_transaction)), 0)) as total_saldo_out
            "))
                ->get();
            if ($listCashAccounts) {
                foreach ($listCashAccounts as $cash) {
                    if ($cash->id == $id_cast_account) {
                        return ($cash->total_saldo_enter - $cash->total_saldo_out);
                    }
                }
            }
        } else if ($status == CastAccount::LOAN) {
            $castAccount = CastAccount::find($id_cast_account);
            $isUsd = ($castAccount && $castAccount->currency_code === 'USD');

            $journal_query = JournalEntry::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id_cast_account) {
                $q->where('cast_account_id', $id_cast_account);
            })->orWhereHasMorph('reference', CastAccount::class, function ($q) use ($id_cast_account) {
                $q->where('id', $id_cast_account);
            });

            if ($isUsd) {
                $journal_ = $journal_query
                    ->select(DB::raw('SUM(debit) - SUM(credit) as total'))
                    ->get();
            } else {
                $journal_ = $journal_query
                    ->select(DB::raw('SUM(IFNULL(debit_base, debit)) - SUM(IFNULL(credit_base, credit)) as total'))
                    ->get();
            }
            if ($journal_) {
                foreach ($journal_ as $journal) {
                    return $journal->total;
                }
            }
        }
    }

    public static function getSettings()
    {
        $setting = Setting::first();
        return $setting;
    }

    public static function clean_html($string)
    {
        $string = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $string);
        $string = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $string);

        return strip_tags($string);
    }

    public static function invoiceEntry($invoice)
    {
        $log_payment = [];
        $invoice_id = $invoice->id;
        // ambil voucher yang belum dibayar

        $log_payment = [];
        // end invoice
        $piutang = Account::where('code', self::getAccountMapping('RECEIVABLES_INVOICE'))->first();
        if ($piutang) {
            $trans_3 = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $piutang->id,
                'reference_id' => $invoice->id,
                'reference_type' => InvoiceClient::class,
                'description' => "Piutang invoice " . $invoice->invoice_number,
                'date' => Carbon::now(),
                'debit' => $invoice->price_total_exclude_ppn,
                'credit' => 0,
            ], [
                'account_id' => $piutang->id,
                'reference_id' => $invoice->id,
                'reference_type' => InvoiceClient::class,
            ]);
            $log_payment[] = [
                'id' => $trans_3->id,
                'account_id' => $piutang->id,
                'reference_id' => $invoice->id,
                'reference_type' => InvoiceClient::class,
                'description' => "Piutang invoice " . $invoice->invoice_number,
                'date' => Carbon::now(),
                'debit' => $invoice->price_total_exclude_ppn,
                'credit' => 0,
                'type' => JournalEntry::class,
            ];
        }

        $acct_ppn = Account::where('code', self::getAccountMapping('DEBT_PPN'))->first();
        if ($acct_ppn) {
            $price_ppn = $invoice->price_total_exclude_ppn * ($invoice->tax_ppn / 100);
            $trans_4 = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $acct_ppn->id,
                'reference_id' => $invoice->id,
                'reference_type' => InvoiceClient::class,
                'description' => "PPN invoice " . $invoice->invoice_number,
                'date' => Carbon::now(),
                'debit' => $price_ppn,
                'credit' => 0,
            ], [
                'account_id' => $acct_ppn->id,
                'reference_id' => $invoice->id,
                'reference_type' => InvoiceClient::class,
            ]);
            $log_payment[] = [
                'id' => $trans_4->id,
                'account_id' => $acct_ppn->id,
                'reference_id' => $invoice->id,
                'reference_type' => InvoiceClient::class,
                'description' => "PPN invoice " . $invoice->invoice_number,
                'date' => Carbon::now(),
                'debit' => $price_ppn,
                'credit' => 0,
                'type' => JournalEntry::class,
            ];
        }
        if (sizeof($log_payment) > 0) {
            $newLogPayment = new LogPayment;
            $newLogPayment->reference_type = InvoiceClient::class;
            $newLogPayment->reference_id = $invoice_id;
            $newLogPayment->name = "CREATE_INVOICE";
            $newLogPayment->snapshot = json_encode($log_payment);
            $newLogPayment->save();
        }
    }

    public static function invoiceUpdate(InvoiceClient $invoice, $old_client_po_id)
    {

        CustomHelper::rollbackPayment(InvoiceClient::class, $invoice->id, "CREATE_INVOICE");

        // invoice client po lama

        if ($old_client_po_id != $invoice->client_po_id) {
            // jika terdapat perbedaan client lama dengan baru

            // cek apakah ada invoice yang menggunakan client po lama
            $exists_other_invoice_old_client_po = InvoiceClient::where('client_po_id', $old_client_po_id)
                ->where('id', '!=', $invoice->id)
                ->first();

            if ($exists_other_invoice_old_client_po == null) {
                // kondisi voucher jadi hutang karena kosong karena invoice tidak exists
                $voucher = Voucher::where('client_po_id', $old_client_po_id)
                    ->get();
                foreach ($voucher as $voucher) {
                    // hapus semua transaksi voucher
                    CustomHelper::rollbackPayment(Voucher::class, $voucher->id);
                    // lalu insert kembali menjadi voucher tanpa invoice
                    CustomHelper::voucherEntry($voucher);
                    CustomHelper::voucherCreate($voucher, true);

                    $voucher->payment_status = 'BELUM BAYAR';
                    $voucher->save();
                }
                // pindahkan biaya pending voucher pada invoice baru
                CustomHelper::invoiceMakeVoucherMoveAccount($invoice);
            }
        }

        CustomHelper::invoiceEntry($invoice);
    }

    public static function invoiceDelete(InvoiceClient $invoice)
    {
        $another_invoice = InvoiceClient::where('client_po_id', $invoice->client_po_id)
            ->where('id', '!=', $invoice->id)->first();

        if ($another_invoice == null) {
            $voucher = Voucher::where('client_po_id', $invoice->client_po_id)->get();
            foreach ($voucher as $voucher) {
                CustomHelper::rollbackPayment(Voucher::class, $voucher->id);
                CustomHelper::voucherEntry($voucher);
                // paksa agar journal voucher menjadi tanpa invoice
                CustomHelper::voucherCreate($voucher, true);

                $voucher->payment_status = 'BELUM BAYAR';
                $voucher->save();
            }
        }

        CustomHelper::rollbackPayment(InvoiceClient::class, $invoice->id, "CREATE_INVOICE");
    }

    public static function invoicePaymentTransaction($transaction, $invoice, &$log_payment, $status = 'out')
    {
        if ($invoice != null) {
            $account_key = ($invoice->withholding_agent == InvoiceClient::WITHHOLDING_AGENT['WAPU']) ? 'WAPU' : 'NON_WAPU';
            $piutang = Account::where('code', self::getAccountMapping($account_key))->first();
            if ($piutang) {
                // Tentukan debit/credit berdasarkan status transaksi
                // Status OUT (Keluar) = Pembayaran invoice = Piutang berkurang (CREDIT)
                // Status ENTER (Masuk) = Pengembalian/Refund = Piutang bertambah (DEBIT)
                $is_out = ($status == AccountTransaction::OUT || $status == 'out');

                $description = $is_out
                    ? "Keluar piutang invoice " . $invoice->invoice_number
                    : "Masuk piutang invoice " . $invoice->invoice_number;

                // Nominal journal yang masuk itu adalah nilai excl ppn
                $nominal = (float) $invoice->price_total_exclude_ppn;
                $currency_code = $transaction->currency_code ?? $invoice->currency_code ?? 'IDR';
                $exchange_rate = (float) ($transaction->exchange_rate ?? $invoice->exchange_rate ?? 1.0);
                $debit = $is_out ? 0 : $nominal;
                $credit = $is_out ? $nominal : 0;

                $piutang_trans = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $piutang->id,
                    'reference_id' => $transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'description' => $description,
                    'date' => Carbon::now(),
                    'currency_code' => $currency_code,
                    'exchange_rate' => $exchange_rate,
                    'debit' => $debit,      // DEBIT jika ENTER
                    'credit' => $credit,    // CREDIT jika OUT
                    'debit_base' => $debit * $exchange_rate,
                    'credit_base' => $credit * $exchange_rate,
                ], [
                    'account_id' => $piutang->id,
                    'reference_id' => $transaction->id,
                    'reference_type' => AccountTransaction::class,
                ]);
                $log_payment[] = [
                    'id' => $piutang_trans->id,
                    'reference_id' => $transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'type' => JournalEntry::class,
                ];
            }
        }
    }

    public static function voucherEntry($voucher)
    {
        $log_payment = [];
        $voucher_id = $voucher->id;
        $currency_code = $voucher->currency_code ?? 'IDR';
        $exchange_rate = (float) ($voucher->exchange_rate ?? 1.0);

        $price_unifikasi = (float) ($voucher->discount_pph_23 + $voucher->discount_pph_4);
        if ($price_unifikasi > 0) {
            $account_unifikasi = Account::where('code', self::getAccountMapping('UNIFICATION'))->first();
            $trans_0 = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $account_unifikasi->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "tambahan pph unifikasi " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'currency_code' => $currency_code,
                'exchange_rate' => $exchange_rate,
                'debit' => $price_unifikasi,
                'credit' => 0,
                'debit_base' => $price_unifikasi * $exchange_rate,
                'credit_base' => 0,
            ], [
                'account_id' => $account_unifikasi->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
            ]);
            $log_payment[] = [
                'id' => $trans_0->id,
                'account_id' => $account_unifikasi->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "tambahan pph unifikasi " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'debit' => $price_unifikasi,
                'credit' => 0,
                'type' => JournalEntry::class,
            ];
        }

        $hutang = Account::where('code', self::getAccountMapping('DEBT_VOUCHER'))->first();
        if ($hutang) {
            $pay_transfer = (float) $voucher->payment_transfer;
            $trans_1 = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $hutang->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "piutang voucher " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'currency_code' => $currency_code,
                'exchange_rate' => $exchange_rate,
                'debit' => $pay_transfer,
                'credit' => 0,
                'debit_base' => $pay_transfer * $exchange_rate,
                'credit_base' => 0,
            ], [
                'account_id' => $hutang->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
            ]);
            $log_payment[] = [
                'id' => $trans_1->id,
                'account_id' => $hutang->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "piutang voucher " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'debit' => $pay_transfer,
                'credit' => 0,
                'type' => JournalEntry::class,
            ];
        }
        if ($voucher->total > 0) {
            $ppn = Account::where('code', self::getAccountMapping('TAX'))->first();
            $total_ppn = (float) ($voucher->bill_value * ($voucher->tax_ppn / 100));
            if ($ppn) {
                $trans_2 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $ppn->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPN voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'currency_code' => $currency_code,
                    'exchange_rate' => $exchange_rate,
                    'debit' => $total_ppn,
                    'credit' => 0,
                    'debit_base' => $total_ppn * $exchange_rate,
                    'credit_base' => 0,
                ], [
                    'account_id' => $ppn->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_2->id,
                    'account_id' => $ppn->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPN voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $total_ppn,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            }
        }
        if ($voucher->discount_pph_23 > 0) {
            $pph_23 = Account::where('code', self::getAccountMapping('PPH_23'))->first();
            if ($pph_23) {
                $disc_23 = (float) $voucher->discount_pph_23;
                $trans_3 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $pph_23->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPH 23 voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'currency_code' => $currency_code,
                    'exchange_rate' => $exchange_rate,
                    'debit' => $disc_23,
                    'credit' => 0,
                    'debit_base' => $disc_23 * $exchange_rate,
                    'credit_base' => 0,
                ], [
                    'account_id' => $pph_23->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_3->id,
                    'account_id' => $pph_23->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPH 23 voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $disc_23,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            }
        }
        if ($voucher->discount_pph_4 > 0) {
            $pph_4 = Account::where('code', self::getAccountMapping('PPH_4'))->first();
            if ($pph_4) {
                $disc_4 = (float) $voucher->discount_pph_4;
                $trans_4 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $pph_4->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPH 4 voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'currency_code' => $currency_code,
                    'exchange_rate' => $exchange_rate,
                    'debit' => $disc_4,
                    'credit' => 0,
                    'debit_base' => $disc_4 * $exchange_rate,
                    'credit_base' => 0,
                ], [
                    'account_id' => $pph_4->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_4->id,
                    'account_id' => $pph_4->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPH 4 voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $disc_4,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            }
        }
        if ($voucher->discount_pph_21 > 0) {
            $pph_21 = Account::where('code', self::getAccountMapping('PPH_21'))->first();
            if ($pph_21) {
                $disc_21 = (float) $voucher->discount_pph_21;
                $trans_5 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $pph_21->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPH 21 voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'currency_code' => $currency_code,
                    'exchange_rate' => $exchange_rate,
                    'debit' => $disc_21,
                    'credit' => 0,
                    'debit_base' => $disc_21 * $exchange_rate,
                    'credit_base' => 0,
                ], [
                    'account_id' => $pph_21->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_5->id,
                    'account_id' => $pph_21->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "PPH 21 voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $voucher->discount_pph_21,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            }
        }
        if (sizeof($log_payment) > 0) {
            $newLogPayment = new LogPayment;
            $newLogPayment->reference_type = Voucher::class;
            $newLogPayment->reference_id = $voucher_id;
            $newLogPayment->name = "CREATE_VOUCHER";
            $newLogPayment->snapshot = json_encode($log_payment);
            $newLogPayment->save();
        }
    }

    public static function invoiceMakeVoucherMoveAccount(InvoiceClient $invoice)
    {
        $before_invoice_id = InvoiceClient::where('id', '!=', $invoice->id)
            ->where('client_po_id', $invoice->client_po_id)
            ->first();

        if ($before_invoice_id == null) {
            // jika invoice pertama
            $voucher = Voucher::where('client_po_id', $invoice->client_po_id)
                ->where('payment_status', 'BELUM BAYAR')
                ->get();
            if ($voucher->count() > 0) {
                foreach ($voucher as $v) {
                    $log_payment_voucher = [];
                    $account_beban = Account::where('code', self::getAccountMapping('WIP'))->first();
                    $payment_transfer = $v->payment_transfer;
                    $trans_1 = CustomHelper::insertJournalEntry([
                        'account_id' => $account_beban->id,
                        'reference_id' => $v->id,
                        'reference_type' => Voucher::class,
                        'description' => "Beban pekerjaan voucher " . $v->no_voucher,
                        'date' => Carbon::now(),
                        'debit' => 0,
                        'credit' => $payment_transfer,
                    ]);
                    $log_payment_voucher[] = [
                        'id' => $trans_1->id,
                        'account_id' => $account_beban->id,
                        'reference_id' => $v->id,
                        'reference_type' => Voucher::class,
                        'description' => "Beban pekerjaan voucher " . $v->no_voucher,
                        'date' => Carbon::now(),
                        'debit' => 0,
                        'credit' => $payment_transfer,
                        'type' => JournalEntry::class,
                    ];

                    $account_pokok = Account::where('id', $v->account_id)->first();
                    $trans_2 = CustomHelper::insertJournalEntry([
                        'account_id' => $account_pokok->id,
                        'reference_id' => $v->id,
                        'reference_type' => Voucher::class,
                        'description' => $v?->client_po?->work_code,
                        'date' => Carbon::now(),
                        'debit' => $payment_transfer,
                        'credit' => 0,
                    ]);
                    $log_payment_voucher[] = [
                        'id' => $trans_2->id,
                        'account_id' => $account_pokok->id,
                        'reference_id' => $v->id,
                        'reference_type' => Voucher::class,
                        'description' => $v?->client_po?->work_code,
                        'date' => Carbon::now(),
                        'debit' => $payment_transfer,
                        'credit' => 0,
                        'type' => JournalEntry::class,
                    ];

                    if (sizeof($log_payment_voucher) > 0) {
                        $newLogPayment = new LogPayment;
                        $newLogPayment->reference_type = Voucher::class;
                        $newLogPayment->reference_id = $v->id;
                        $newLogPayment->name = "BALANCE_VOUCHER_WITH_INVOICE";
                        $newLogPayment->snapshot = json_encode($log_payment_voucher);
                        $newLogPayment->save();
                    }
                }
            }
        }
    }

    public static function voucherPayment($voucher)
    {
        $client_po = $voucher->client_po;
        $cast_account = CastAccount::where('id', $voucher->account_source_id)->first();
        $log_payment = [];

        // kurangi hutang
        $hutang = Account::where('code', self::getAccountMapping('DEBT_VOUCHER'))->first();
        if ($hutang) {
            $trans_1 = CustomHelper::insertJournalEntry([
                'account_id' => $hutang->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "piutang voucher " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'debit' => 0,
                'credit' => $voucher->payment_transfer,
            ]);
            $log_payment[] = [
                'id' => $trans_1->id,
                'account_id' => $hutang->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "piutang voucher " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'type' => JournalEntry::class,
                'debit' => 0,
                'credit' => $voucher->payment_transfer,
            ];
        }

        $transaksi = new AccountTransaction;
        $transaksi->cast_account_id = $voucher->account_source_id;
        $transaksi->reference_type = Voucher::class;
        $transaksi->reference_id = $voucher->id;
        $transaksi->date_transaction = $voucher->payment_date;
        $transaksi->nominal_transaction = $voucher->payment_transfer;
        $transaksi->total_saldo_before = 0;
        $transaksi->total_saldo_after = 0;
        $transaksi->status = CastAccount::OUT;
        $transaksi->kdp = $client_po?->work_code;
        $transaksi->job_name = $voucher?->job_name;
        $transaksi->save();

        $log_payment[] = [
            'id' => $transaksi->id,
            'account_id' => $hutang->id,
            'reference_id' => $voucher->id,
            'reference_type' => Voucher::class,
            'description' => 'Pembayaran Voucher',
            'date' => Carbon::now(),
            'type' => AccountTransaction::class,
        ];

        $accountBank = Account::where('id', $cast_account->account_id)->first();
        if ($accountBank) {
            $trans_2 = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $accountBank->id,
                'reference_id' => $transaksi->id,
                'reference_type' => AccountTransaction::class,
                'description' => "Saldo berkurang " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'debit' => 0,
                'credit' => $transaksi->nominal_transaction,
            ], [
                'account_id' => $accountBank->id,
                'reference_id' => $transaksi->id,
                'reference_type' => AccountTransaction::class,
            ]);
            $log_payment[] = [
                'id' => $trans_2->id,
                'account_id' => $accountBank->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "Saldo berkurang " . $voucher->no_voucher,
                'date' => Carbon::now(),
                'type' => JournalEntry::class,
                'debit' => 0,
                'credit' => $transaksi->nominal_transaction,
            ];
        }

        if (sizeof($log_payment) > 0) {
            $newLogPayment = new LogPayment;
            $newLogPayment->reference_type = Voucher::class;
            $newLogPayment->reference_id = $voucher->id;
            $newLogPayment->name = "CREATE_PAYMENT_VOUCHER";
            $newLogPayment->snapshot = json_encode($log_payment);
            $newLogPayment->save();
        }
    }

    public static function balanceAccount($account_code, $startDate = null, $endDate = null)
    {
        $code = $account_code;

        if ($code == self::getAccountMapping('PROFIT_CONSOLIDATION')) {
            return self::getNetProfit($startDate, $endDate);
        }

        $query = \App\Models\Account::selectRaw("
            (SUM(journal_entries.debit) - SUM(journal_entries.credit)) as balance
        ")
            ->leftJoin('journal_entries', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.code', 'LIKE', "" . $code . "%");

        if ($startDate && $endDate) {
            $query->whereBetween('journal_entries.date', [$startDate, $endDate]);
        }

        $sum_accounts = $query->first();
        return $sum_accounts->balance ?? 0;
    }

    public static function getNetProfit($startDate = null, $endDate = null)
    {
        $dataset = [];
        $consolidate_income_header = DB::table('consolidate_income_headers')
            ->orderBy('id', 'asc')->get();

        if (count($consolidate_income_header) < 8) {
            return 0;
        }

        // pendapatan usaha (0)
        $contract_income_number = 0;
        $items0 = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
            ->where('consolidate_income_account_items.header_id', $consolidate_income_header[0]->id)
            ->select(DB::raw("accounts.*"))->get();
        foreach ($items0 as $item) {
            $contract_income_number += self::balanceAccount($item->code, $startDate, $endDate);
        }

        // beban usaha (1)
        $cost_expense_number = 0;
        $items1 = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
            ->where('consolidate_income_account_items.header_id', $consolidate_income_header[1]->id)
            ->select(DB::raw("accounts.*"))->get();
        foreach ($items1 as $item) {
            $cost_expense_number += self::balanceAccount($item->code, $startDate, $endDate);
        }

        // laba usaha
        $cost_profit_expense_number = $contract_income_number - $cost_expense_number;

        // pendapatan lain - lain (3)
        $cost_other_number = 0;
        $items3 = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
            ->where('consolidate_income_account_items.header_id', $consolidate_income_header[3]->id)
            ->select(DB::raw("accounts.*"))->get();
        foreach ($items3 as $item) {
            $cost_other_number += self::balanceAccount($item->code, $startDate, $endDate);
        }

        // beban lain - lain (4)
        $expense_other_number = 0;
        $items4 = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
            ->where('consolidate_income_account_items.header_id', $consolidate_income_header[4]->id)
            ->select(DB::raw("accounts.*"))->get();
        foreach ($items4 as $item) {
            $expense_other_number += self::balanceAccount($item->code, $startDate, $endDate);
        }

        // laba sebelum pajak
        $profit_before_tax = $cost_profit_expense_number + $cost_other_number - $expense_other_number;

        // beban pajak (6)
        $expense_tax_number = 0;
        $items6 = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
            ->where('consolidate_income_account_items.header_id', $consolidate_income_header[6]->id)
            ->select(DB::raw("accounts.*"))->get();
        foreach ($items6 as $item) {
            $expense_tax_number += self::balanceAccount($item->code, $startDate, $endDate);
        }

        // laba bersih
        $net_profit = $profit_before_tax - $expense_tax_number;

        return $net_profit;
    }

    public static function voucherCreate(Voucher $voucher, $invoice_not_exists = false)
    {
        $log_payment = [];
        // $voucher = Voucher::where('id', $voucher_id)->first();
        $invoice = InvoiceClient::where('client_po_id', $voucher->client_po_id)->first();
        $client_po = $voucher->client_po;
        $payment_transfer = $voucher->payment_transfer;

        if ($client_po->status == 'TANPA PO') {
            // ada po
            $account = Account::where('code', self::getAccountMapping('WITHOUT_PO'))->first();

            $trans_1 = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $account->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "Transaksi tanpa PO " . $client_po->work_code,
                'date' => Carbon::now(),
                'debit' => $payment_transfer,
                // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
            ], [
                'account_id' => $account->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
            ]);
            $log_payment[] = [
                'id' => $trans_1->id,
                'account_id' => $account->id,
                'reference_id' => $voucher->id,
                'reference_type' => Voucher::class,
                'description' => "Transaksi tanpa PO " . $client_po->work_code,
                'date' => Carbon::now(),
                'debit' => $payment_transfer,
                'type' => JournalEntry::class,
            ];
        }

        // periksa jenis voucher
        if ($voucher->reference_type == "App\Models\PurchaseOrder" || $voucher->reference_type == "App\Models\Spk") {
            if ($invoice == null || $invoice_not_exists == true) {
                $account = Account::where('code', self::getAccountMapping('WIP'))->first();
                $trans_2 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Beban dalam proses pekerjaan voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_2->id,
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Beban dalam proses pekerjaan voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            } else {
                $account = Account::where('id', $voucher->account_id)->first();
                $payment_transfer = $voucher->payment_transfer;

                $trans_3 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Transaksi voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_3->id,
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Transaksi voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            }
        } else if ($voucher->reference_type == "App\Models\ClientPo") {
            if ($invoice == null || $invoice_not_exists == true) {
                // jika tidak ada invoice di PO
                $account = Account::where('code', self::getAccountMapping('WIP'))->first();
                $payment_transfer = $voucher->payment_transfer;
                $trans_4 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Beban dalam proses pekerjaan voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_4->id,
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Beban dalam proses pekerjaan voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            } else {
                $account = Account::where('id', $voucher->account_id)->first();
                $payment_transfer = $voucher->payment_transfer;

                $invoice->status = 'Paid';
                $invoice->save();

                $trans_5 = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Transaksi voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                ]);
                $log_payment[] = [
                    'id' => $trans_5->id,
                    'account_id' => $account->id,
                    'reference_id' => $voucher->id,
                    'reference_type' => Voucher::class,
                    'description' => "Transaksi voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    'type' => JournalEntry::class,
                ];
            }
        }

        if (sizeof($log_payment) > 0) {
            $newLogPayment = new LogPayment;
            $newLogPayment->reference_type = Voucher::class;
            $newLogPayment->reference_id = $voucher->id;
            $newLogPayment->name = "CREATE_VOUCHER";
            $newLogPayment->snapshot = json_encode($log_payment);
            $newLogPayment->save();
        }
    }

    public static function profitLostRepository($filter = [])
    {
        $voucher = DB::table('vouchers')->select(
            'client_po_id',
            DB::raw("SUM(payment_transfer) as payment_transfer"),
            DB::raw("SUM(total) as biaya")
        )->groupBy('client_po_id');

        $invoice = DB::table('invoice_clients')
            ->select(
                'invoice_clients.client_po_id',
                DB::raw("GROUP_CONCAT(invoice_clients.invoice_date SEPARATOR ',') AS invoice_date"),
                DB::raw("SUM(invoice_clients.price_total_exclude_ppn) as price_job_exlude_ppn"),
                DB::raw("SUM(invoice_clients.price_total_include_ppn) as price_job_include_ppn")
            )
            ->when(!empty($filter['filter_year']), function ($query) use ($filter) {
                $filter_year = $filter['filter_year'];
                if ($filter_year && $filter_year != 'all') {
                    return $query->whereYear('invoice_clients.invoice_date', $filter_year);
                }
            })
            ->groupBy('invoice_clients.client_po_id');

        $client_po_query_exclude_ppn = DB::table("client_po")
            ->leftJoinSub($invoice, 'invoice', function ($join) {
                $join->on('invoice.client_po_id', '=', 'client_po.id');
            })->select(
                "client_po.id as client_po_id",
                "client_po.work_code",
                "client_po.po_number",
                "client_po.reimburse_type",
                "client_po.job_name",
                "client_po.job_value",
                "client_po.category",
                "client_po.job_value_include_ppn",
                "client_po.date_po",
                "invoice.invoice_date",
                "invoice.price_job_exlude_ppn as invoice_price_job_exlude_ppn",
                "invoice.price_job_include_ppn as invoice_price_job_include_ppn",
                DB::raw("IF(invoice.invoice_date IS NULL, client_po.job_value, invoice.price_job_exlude_ppn) as price_job_exlude_ppn_logic"),
                DB::raw("IF(invoice.invoice_date IS NULL, 0, invoice.price_job_include_ppn) as job_value_include_ppn_logic")
            );
        $profitLost = ProjectProfitLost::leftJoinSub($client_po_query_exclude_ppn, 'client_po', function ($join) {
            $join->on('client_po.client_po_id', '=', 'project_profit_lost.client_po_id');
        });
        $profitLost = $profitLost->leftJoinSub($voucher, 'vouchers', function ($join) {
            $join->on('vouchers.client_po_id', '=', 'project_profit_lost.client_po_id');
        });
        $profitLost = $profitLost->select(
            DB::raw("
                project_profit_lost.*,
                vouchers.payment_transfer as payment_voucher,
                vouchers.biaya as voucher_biaya,
                client_po.invoice_price_job_exlude_ppn,
                client_po.invoice_date,
                client_po.price_job_exlude_ppn_logic,
                client_po.invoice_price_job_include_ppn,
                client_po.work_code as work_code,
                client_po.po_number as po_number,
                client_po.reimburse_type as reimburse_type,
                client_po.job_name as job_name,
                client_po.job_value as job_value,
                client_po.date_po,
                client_po.job_value_include_ppn_logic as job_value_include_ppn,
                IFNULL(project_profit_lost.price_small_cash, 0) as total_small_cash,
                (IFNULL(project_profit_lost.price_after_year, 0) + IFNULL(vouchers.biaya, 0) + IFNULL(project_profit_lost.price_small_cash, 0)) as price_total_str,
                (client_po.price_job_exlude_ppn_logic - (IFNULL(project_profit_lost.price_after_year, 0) + IFNULL(vouchers.biaya, 0) + IFNULL(project_profit_lost.price_small_cash, 0))) as price_profit_lost_str,
                ((client_po.price_job_exlude_ppn_logic - (IFNULL(project_profit_lost.price_after_year, 0) + IFNULL(vouchers.biaya, 0) + IFNULL(project_profit_lost.price_small_cash, 0))) - IFNULL(project_profit_lost.price_general, 0)) as price_prift_lost_final_str
            ")
        );

        return $profitLost;
    }

    public static function profitLostSupplierRepository($filter = [])
    {
        $v_supp = DB::table('vouchers')
            ->select('client_po_id', DB::raw('SUM(bill_value_base) as total_voucher_supplier_base'))
            ->where('po_type', 'supplier')
            ->groupBy('client_po_id');

        $supplierSubQuery = DB::table('invoice_clients as ic')
            ->leftJoin('clients as c', 'c.id', '=', 'ic.client_id')
            ->leftJoin('invoice_client_details as icd', 'icd.invoice_client_id', '=', 'ic.id')
            ->leftJoin('client_po as cpo', 'cpo.id', '=', 'ic.client_po_id')
            ->leftJoinSub($v_supp, 'v_supp', function ($join) {
                $join->on('v_supp.client_po_id', '=', 'cpo.id');
            })
            ->where('ic.type_device', 'App\\Models\\DeviceStock')
            ->when(!empty($filter['filter_year']), function ($query) use ($filter) {
                $filter_year = $filter['filter_year'];
                if ($filter_year && $filter_year != 'all') {
                    return $query->whereYear('ic.invoice_date', $filter_year);
                }
            })
            ->select([
                'ic.id as invoice_id',
                'ic.invoice_number as supplier_invoice_number',
                'ic.invoice_date as supplier_date',
                'ic.category as invoice_category',
                'c.name as supplier_name',
                'ic.currency_code',
                DB::raw("COALESCE(SUM(icd.qty), 0) AS total_qty_sold"),
                DB::raw("ic.price_total_exclude_ppn_base AS total_harga_jual_base"),
                DB::raw("ic.price_total_exclude_ppn_base AS sell_value"),
                DB::raw("CASE WHEN SUM(icd.qty) > 0 THEN ROUND(ic.price_total_exclude_ppn_base / SUM(icd.qty), 2) ELSE 0 END AS avg_harga_jual_satuan_base"),
                DB::raw("COALESCE(SUM(icd.cogs_amount_base), 0) AS total_harga_beli_gross_base"),
                DB::raw("COALESCE(v_supp.total_voucher_supplier_base, 0) AS total_voucher_supplier_base"),
                DB::raw("(COALESCE(SUM(icd.cogs_amount_base), 0) - COALESCE(v_supp.total_voucher_supplier_base, 0)) AS total_harga_beli_base"),
                DB::raw("(COALESCE(SUM(icd.cogs_amount_base), 0) - COALESCE(v_supp.total_voucher_supplier_base, 0)) AS purchase_value"),
                DB::raw("CASE WHEN SUM(icd.qty) > 0 THEN ROUND((COALESCE(SUM(icd.cogs_amount_base), 0) - COALESCE(v_supp.total_voucher_supplier_base, 0)) / SUM(icd.qty), 2) ELSE 0 END AS avg_harga_beli_satuan_base"),
                DB::raw("(ic.price_total_exclude_ppn_base - (COALESCE(SUM(icd.cogs_amount_base), 0) - COALESCE(v_supp.total_voucher_supplier_base, 0))) AS laba_kotor_base"),
                DB::raw("CASE WHEN ic.price_total_exclude_ppn_base > 0 THEN ROUND(((ic.price_total_exclude_ppn_base - (COALESCE(SUM(icd.cogs_amount_base), 0) - COALESCE(v_supp.total_voucher_supplier_base, 0))) / ic.price_total_exclude_ppn_base) * 100, 2) ELSE 0 END AS margin_percent"),
                DB::raw("CASE WHEN ic.delivery_note_id IS NOT NULL OR EXISTS (SELECT 1 FROM delivery_notes dn WHERE dn.invoice_client_id = ic.id) THEN 'Terbit Surat Jalan & FIFO Posted' ELSE 'Invoice Tanpa Surat Jalan' END AS delivery_status")
            ])
            ->groupBy(
                'ic.id',
                'ic.invoice_number',
                'ic.invoice_date',
                'ic.category',
                'c.name',
                'ic.currency_code',
                'ic.price_total_exclude_ppn_base',
                'ic.delivery_note_id',
                'v_supp.total_voucher_supplier_base'
            );

        return DB::table('project_profit_lost')
            ->where('project_profit_lost.orderable_type', 'App\\Models\\InvoiceClient')
            ->joinSub($supplierSubQuery, 'supplier_data', function ($join) {
                $join->on('supplier_data.invoice_id', '=', 'project_profit_lost.orderable_id');
            })
            ->select([
                DB::raw("
                    project_profit_lost.*,
                    supplier_data.supplier_name,
                    supplier_data.supplier_invoice_number,
                    supplier_data.supplier_date,
                    supplier_data.invoice_category,
                    supplier_data.currency_code,
                    supplier_data.total_qty_sold,
                    supplier_data.total_harga_jual_base,
                    supplier_data.total_harga_jual_base as sell_value,
                    supplier_data.avg_harga_jual_satuan_base,
                    supplier_data.total_harga_beli_gross_base,
                    supplier_data.total_harga_beli_base,
                    supplier_data.total_harga_beli_base as purchase_value,
                    supplier_data.avg_harga_beli_satuan_base,
                    supplier_data.laba_kotor_base,
                    supplier_data.laba_kotor_base as profit_lost_supplier,
                    supplier_data.margin_percent,
                    supplier_data.delivery_status,
                    supplier_data.total_voucher_supplier_base as voucher_supplier_value
                ")
            ]);
    }

    public static function rollbackPayment($reference_type, $reference_id, $name = null)
    {
        $payment = LogPayment::where('reference_type', $reference_type)
            ->where('reference_id', $reference_id);
        if ($name) {
            $payment = $payment->where('name', $name);
        }
        $payment = $payment->get();
        foreach ($payment as $pay) {
            // call child for all header log transaction
            $snapshots = json_decode($pay->snapshot);
            foreach ($snapshots as $snapshot) {
                $snap_id = $snapshot->id;
                $objTable = new $snapshot->type;
                if (is_object($objTable)) {
                    $dataset = $objTable::find($snap_id);
                    if ($dataset) {
                        $dataset->delete();
                    }
                }
            }
            // delete header log transaction
            $pay->delete();
        }
        return 1;
    }
}
