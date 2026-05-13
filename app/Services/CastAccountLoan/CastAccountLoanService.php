<?php

namespace App\Services\CastAccountLoan;

use App\Models\CastAccount;
use App\Models\AccountTransaction;
use App\Models\LoanTransactionFlag;
use App\Models\JournalEntry;
use App\Models\LogPayment;
use App\DTOs\CastAccountLoan\CastAccountLoanSaveData;
use App\DTOs\CastAccountLoan\LoanTransactionSaveData;
use App\DTOs\CastAccountLoan\LoanMoveTransactionSaveData;
use App\Http\Helpers\CustomHelper;
use App\Http\Helpers\CustomVoid;
use App\Models\GlobalChangedLogs;
use App\Repositories\CastAccountLoan\CastAccountLoanRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CastAccountLoanService
{
    public function __construct(
        protected CastAccountLoanRepository $repository
    ) {}

    public function storeLoanAccount(CastAccountLoanSaveData $data): CastAccount
    {
        return DB::transaction(function () use ($data) {
            if ($data->id) {
                $item = CastAccount::findOrFail($data->id);
                $item->update($data->toArray());
                return $item;
            }

            $item = CastAccount::create($data->toArray());

            if ($item->status == CastAccount::LOAN && $item->total_saldo > 0) {
                $log_payment = [];
                $codeLoan = $this->generateCodeLoan();
                $dateTransactionInit = $data->date_transaction_init ? Carbon::parse($data->date_transaction_init) : Carbon::now();

                $loan_transaction_flag = LoanTransactionFlag::create([
                    'kode' => $codeLoan,
                    'total_price' => $item->total_saldo,
                ]);

                $log_payment[] = [
                    'id' => $loan_transaction_flag->id,
                    'reference_id' => $item->id,
                    'reference_type' => CastAccount::class,
                    'type' => LoanTransactionFlag::class,
                ];

                $loan_transaction = AccountTransaction::create([
                    'cast_account_id' => $item->id,
                    'date_transaction' => $dateTransactionInit,
                    'nominal_transaction' => $item->total_saldo,
                    'total_saldo_before' => $item->total_saldo,
                    'total_saldo_after' => $item->total_saldo,
                    'status' => 'enter',
                    'account_id' => $item->account_id,
                    'description' => "Saldo Awal Pinjaman",
                    'reference_type' => LoanTransactionFlag::class,
                    'reference_id' => $loan_transaction_flag->id,
                ]);

                $log_payment[] = [
                    'id' => $loan_transaction->id,
                    'reference_id' => $item->id,
                    'reference_type' => CastAccount::class,
                    'type' => AccountTransaction::class,
                ];

                $journal = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $item->account_id,
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'description' => $loan_transaction->description,
                    'date' => $dateTransactionInit,
                    'debit' => $item->total_saldo,
                ], [
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                ]);

                $log_payment[] = [
                    'id' => $journal->id,
                    'reference_id' => $item->id,
                    'reference_type' => CastAccount::class,
                    'type' => JournalEntry::class,
                ];

                if (count($log_payment) > 0) {
                    LogPayment::create([
                        'reference_type' => CastAccount::class,
                        'reference_id' => $item->id,
                        'name' => "CREATE_TRANSACTION",
                        'snapshot' => json_encode($log_payment),
                    ]);
                }
            }

            return $item;
        });
    }

    public function storeTransaction(LoanTransactionSaveData $data): CastAccount
    {
        return DB::transaction(function () use ($data) {
            $cast_account_loan = CastAccount::findOrFail($data->cast_account_id);
            $codeLoan = $this->generateCodeLoan();
            $log_payment = [];

            if ($data->cast_account_destination_id == AccountTransaction::BANK_LOAN) {
                $total_saldo_loan_before = CustomHelper::balanceAccount($cast_account_loan->account->code);
                $total_saldo_loan_after = $total_saldo_loan_before + $data->nominal_transaction;

                $acctTransactionLoan = AccountTransaction::create([
                    'cast_account_id' => $data->cast_account_id,
                    'date_transaction' => $data->date_transaction,
                    'nominal_transaction' => $data->nominal_transaction,
                    'total_saldo_before' => $total_saldo_loan_before,
                    'total_saldo_after' => $total_saldo_loan_after,
                    'status' => $data->status,
                    'account_id' => $cast_account_loan->account_id,
                    'description' => $data->description,
                ]);

                $log_payment[] = [
                    'id' => $acctTransactionLoan->id,
                    'reference_id' => $acctTransactionLoan->id,
                    'reference_type' => AccountTransaction::class,
                    'type' => AccountTransaction::class,
                ];

                $old_cast_account = clone $cast_account_loan;
                $cast_account_loan->update(['total_saldo' => $total_saldo_loan_after]);

                $journal = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $acctTransactionLoan->account_id,
                    'reference_id' => $acctTransactionLoan->id,
                    'reference_type' => AccountTransaction::class,
                    'description' => $data->description,
                    'date' => Carbon::now(),
                    'debit' => $data->nominal_transaction,
                    'credit' => 0,
                ], [
                    'reference_id' => $acctTransactionLoan->id,
                    'reference_type' => AccountTransaction::class,
                ]);

                $log_payment[] = [
                    'id' => $journal->id,
                    'reference_id' => $acctTransactionLoan->id,
                    'reference_type' => AccountTransaction::class,
                    'type' => JournalEntry::class,
                ];
            } else {
                $cast_account_destination = CastAccount::findOrFail($data->cast_account_destination_id);

                $loan_transaction_flag = LoanTransactionFlag::create([
                    'kode' => $codeLoan,
                    'total_price' => $data->nominal_transaction,
                ]);

                $log_payment[] = [
                    'id' => $loan_transaction_flag->id,
                    'reference_id' => $loan_transaction_flag->id, // Will be updated below if needed
                    'reference_type' => LoanTransactionFlag::class,
                    'type' => LoanTransactionFlag::class,
                ];

                $loan_transaction = AccountTransaction::create([
                    'cast_account_id' => $data->cast_account_id,
                    'cast_account_destination_id' => $data->cast_account_destination_id,
                    'date_transaction' => $data->date_transaction,
                    'nominal_transaction' => $data->nominal_transaction,
                    'total_saldo_before' => $data->nominal_transaction,
                    'total_saldo_after' => $data->nominal_transaction,
                    'status' => $data->status,
                    'account_id' => $cast_account_loan->account_id,
                    'description' => $data->description,
                    'reference_type' => LoanTransactionFlag::class,
                    'reference_id' => $loan_transaction_flag->id,
                ]);

                $log_payment[] = [
                    'id' => $loan_transaction->id,
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'type' => AccountTransaction::class,
                ];

                $add_transaction_destination = AccountTransaction::create([
                    'cast_account_id' => $data->cast_account_destination_id,
                    'cast_account_destination_id' => $data->cast_account_id,
                    'date_transaction' => $data->date_transaction,
                    'nominal_transaction' => $data->nominal_transaction,
                    'total_saldo_before' => 0,
                    'total_saldo_after' => 0,
                    'status' => $data->status,
                    'account_id' => $cast_account_destination->account_id,
                    'description' => $data->description,
                ]);

                $log_payment[] = [
                    'id' => $add_transaction_destination->id,
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'type' => AccountTransaction::class,
                ];

                $journal_loan = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $cast_account_loan->account_id,
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'description' => $data->description,
                    'date' => Carbon::now(),
                    'debit' => ($data->status == 'enter') ? $data->nominal_transaction : 0,
                    'credit' => ($data->status == 'out') ? $data->nominal_transaction : 0,
                ], [
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                ]);

                $log_payment[] = [
                    'id' => $journal_loan->id,
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'type' => JournalEntry::class,
                ];

                $journal_dest = CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $cast_account_destination->account_id,
                    'reference_id' => $add_transaction_destination->id,
                    'reference_type' => AccountTransaction::class,
                    'description' => $data->description,
                    'date' => Carbon::now(),
                    'debit' => ($data->status == 'enter') ? $data->nominal_transaction : 0,
                    'credit' => ($data->status == 'out') ? $data->nominal_transaction : 0,
                ], [
                    'reference_id' => $add_transaction_destination->id,
                    'reference_type' => AccountTransaction::class,
                ]);

                $log_payment[] = [
                    'id' => $journal_dest->id,
                    'reference_id' => $loan_transaction->id,
                    'reference_type' => AccountTransaction::class,
                    'type' => JournalEntry::class,
                ];
            }

            if (count($log_payment) > 0) {
                // Determine reference for LogPayment
                $ref_id = ($data->cast_account_destination_id == AccountTransaction::BANK_LOAN) ? $acctTransactionLoan->id : $loan_transaction->id;
                $newLogPayment = LogPayment::create([
                    'reference_type' => AccountTransaction::class,
                    'reference_id' => $ref_id,
                    'name' => "CREATE_TRANSACTION",
                    'snapshot' => json_encode($log_payment),
                ]);

                if (isset($old_cast_account)) {
                    GlobalChangedLogs::addCapture([
                        'total_saldo',
                    ], $old_cast_account, $cast_account_loan, $newLogPayment->id);
                }
            }

            return $cast_account_loan;
        });
    }

    public function storeMoveTransaction(LoanMoveTransactionSaveData $data): AccountTransaction
    {
        return DB::transaction(function () use ($data) {
            $account_destination = CastAccount::findOrFail($data->cast_account_destination_id)->account;
            $balance_destination_before = CustomHelper::balanceAccount($account_destination->code);
            $balance_destination_after = $balance_destination_before - $data->payment_price;
            $log_payment = [];

            $first_account_transaction = $this->repository->getFirstAccountTransactionByFlagId($data->loan_transaction_flag_id);
            $total_loan_out_before = $this->repository->getTotalLoanOutByFlagId($data->loan_transaction_flag_id);
            $loan_transaction_flag = $this->repository->findLoanTransactionFlagById($data->loan_transaction_flag_id);

            $remaining_balance = $loan_transaction_flag->total_price - $total_loan_out_before - $data->payment_price;

            $new_cast_transaction = AccountTransaction::create([
                'cast_account_id' => $data->cast_account_destination_id,
                'cast_account_destination_id' => $data->cast_account_id,
                'reference_type' => LoanTransactionFlag::class,
                'reference_id' => $data->loan_transaction_flag_id,
                'date_transaction' => $data->date_loan_transaction,
                'description' => $data->description,
                'account_id' => $account_destination->id,
                'nominal_transaction' => $data->payment_price,
                'total_saldo_before' => $balance_destination_before,
                'total_saldo_after' => $balance_destination_after,
                'status' => 'out',
            ]);

            $log_payment[] = [
                'id' => $new_cast_transaction->id,
                'reference_id' => $new_cast_transaction->id, // temporary
                'reference_type' => AccountTransaction::class,
                'type' => AccountTransaction::class,
            ];

            $journal_dest = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $account_destination->id,
                'reference_id' => $new_cast_transaction->id,
                'reference_type' => AccountTransaction::class,
                'description' => $data->description,
                'date' => $data->date_loan_transaction,
                'debit' => 0,
                'credit' => $data->payment_price,
            ], [
                'reference_id' => $new_cast_transaction->id,
                'reference_type' => AccountTransaction::class,
            ]);

            $log_payment[] = [
                'id' => $journal_dest->id,
                'reference_id' => $new_cast_transaction->id,
                'reference_type' => AccountTransaction::class,
                'type' => JournalEntry::class,
            ];

            $new_loan_transaction = AccountTransaction::create([
                'cast_account_id' => $data->cast_account_id,
                'cast_account_destination_id' => null,
                'reference_type' => LoanTransactionFlag::class,
                'reference_id' => $data->loan_transaction_flag_id,
                'date_transaction' => $data->date_loan_transaction,
                'description' => $data->description,
                'account_id' => $first_account_transaction->account_id,
                'nominal_transaction' => $data->payment_price,
                'total_saldo_before' => $total_loan_out_before,
                'total_saldo_after' => $remaining_balance,
                'status' => 'out',
            ]);

            $log_payment[] = [
                'id' => $new_loan_transaction->id,
                'reference_id' => $new_cast_transaction->id,
                'reference_type' => AccountTransaction::class,
                'type' => AccountTransaction::class,
            ];

            $journal_loan = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $first_account_transaction->account_id,
                'reference_id' => $new_loan_transaction->id,
                'reference_type' => AccountTransaction::class,
                'description' => $data->description,
                'date' => $data->date_loan_transaction,
                'debit' => 0,
                'credit' => $data->payment_price,
            ], [
                'reference_id' => $new_loan_transaction->id,
                'reference_type' => AccountTransaction::class,
            ]);

            $log_payment[] = [
                'id' => $journal_loan->id,
                'reference_id' => $new_cast_transaction->id,
                'reference_type' => AccountTransaction::class,
                'type' => JournalEntry::class,
            ];

            if ($remaining_balance <= 0) {
                $old_loan_flag = clone $loan_transaction_flag;
                $loan_transaction_flag->update(['status' => 1]);
            }

            if (count($log_payment) > 0) {
                foreach ($log_payment as &$log) {
                    $log['reference_id'] = $new_cast_transaction->id;
                    $log['reference_type'] = AccountTransaction::class;
                }
                $newLogPayment = LogPayment::create([
                    'reference_type' => AccountTransaction::class,
                    'reference_id' => $new_cast_transaction->id,
                    'name' => "CREATE_TRANSACTION",
                    'snapshot' => json_encode($log_payment),
                ]);

                if (isset($old_loan_flag)) {
                    GlobalChangedLogs::addCapture([
                        'status',
                    ], $old_loan_flag, $loan_transaction_flag, $newLogPayment->id);
                }
            }

            return $new_loan_transaction;
        });
    }

    public function deleteLoanAccount(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $all_transaction = AccountTransaction::where('cast_account_id', $id)
                ->orWhere('cast_account_destination_id', $id)
                ->get();

            foreach ($all_transaction as $transaction) {
                CustomVoid::rollbackPayment(AccountTransaction::class, $transaction->id);
            }

            return (bool) CastAccount::destroy($id);
        });
    }

    private function generateCodeLoan(): string
    {
        do {
            $kode = 'LOAN-' . strtoupper(Str::random(8));
            $check = LoanTransactionFlag::where('kode', $kode)->first();
        } while ($check != null);
        return $kode;
    }
}
