<?php

namespace App\Services\CastAccount;

use App\Models\CastAccount;
use App\Models\AccountTransaction;
use App\Models\LogPayment;
use App\Models\JournalEntry;
use App\DTOs\CastAccount\CastAccountSaveData;
use App\DTOs\CastAccount\TransactionSaveData;
use App\Http\Helpers\CustomHelper;
use App\Http\Helpers\CustomVoid;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CastAccountService
{
    public function storeCastAccount(CastAccountSaveData $data): CastAccount
    {
        return DB::transaction(function () use ($data) {
            $castAccount = CastAccount::create($data->toArray());
            
            if (!empty($data->informations)) {
                $castAccount->informations()->sync($data->informations);
            }

            // Initial transaction
            $acctTransaction = new AccountTransaction;
            $acctTransaction->cast_account_id = $castAccount->id;
            $acctTransaction->date_transaction = Carbon::now()->format('Y-m-d');
            $acctTransaction->nominal_transaction = $data->total_saldo;
            $acctTransaction->total_saldo_before = 0;
            $acctTransaction->total_saldo_after = $data->total_saldo;
            $acctTransaction->status = CastAccount::ENTER;
            $acctTransaction->is_first = 1;
            $acctTransaction->save();

            CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $castAccount->account_id,
                'reference_id' => $castAccount->id,
                'reference_type' => CastAccount::class,
                'description' => 'Start Saldo',
                'date' => Carbon::now(),
                'debit' => $data->total_saldo,
            ], [
                'reference_id' => $castAccount->id,
                'reference_type' => CastAccount::class,
            ]);

            return $castAccount;
        });
    }

    public function updateCastAccount(int $id, CastAccountSaveData $data): CastAccount
    {
        return DB::transaction(function () use ($id, $data) {
            $castAccount = CastAccount::findOrFail($id);
            $castAccount->update($data->toArray());

            if (isset($data->informations)) {
                $castAccount->informations()->sync($data->informations);
            }

            return $castAccount;
        });
    }

    public function deleteCastAccount(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $castAccount = CastAccount::findOrFail($id);
            $accountTransactions = AccountTransaction::where('cast_account_id', $id)
                ->orWhere('cast_account_destination_id', $id)
                ->get();

            foreach ($accountTransactions as $transaction) {
                CustomVoid::rollbackPayment(AccountTransaction::class, $transaction->id);
            }

            return $castAccount->delete();
        });
    }

    public function storeTransaction(TransactionSaveData $data): AccountTransaction
    {
        return DB::transaction(function () use ($data) {
            $fakeRequest = new \Illuminate\Http\Request($data->toArray());
            return CustomVoid::storeTransaction($fakeRequest, $data->status);
        });
    }

    public function updateTransaction(int $id, TransactionSaveData $data): AccountTransaction
    {
        return DB::transaction(function () use ($id, $data) {
            $old_item = AccountTransaction::findOrFail($id);
            $request = request();

            $log_payment_exists = LogPayment::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id) {
                $q->where('id', $id);
            })->exists();

            if ($log_payment_exists) {
                if ($data->kdp || $data->no_invoice) {
                    $invoiceId = $data->kdp ?? $data->no_invoice;
                    \App\Models\InvoiceClient::where('id', $invoiceId)->first()?->id; // Just ensuring it exists
                    CustomVoid::rollbackPayment(\App\Models\InvoiceClient::class, $invoiceId, 'CREATE_PAYMENT_INVOICE');
                }
                CustomVoid::rollbackPayment(AccountTransaction::class, $id);
                return CustomVoid::storeTransaction($request, $data->status);
            } else {
                $journal = JournalEntry::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id) {
                    $q->where('id', $id);
                })->get();

                $old_item->date_transaction = $data->date_transaction;
                $old_item->description = $data->description;
                $old_item->account_id = $data->account_id;
                $old_item->no_invoice = $data->no_invoice;
                $old_item->nominal_transaction = $data->nominal_transaction;
                $old_item->save();

                foreach ($journal as $j) {
                    $j->description = $old_item->description;
                    $j->account_id = $old_item->account_id;
                    $j->date = $old_item->date_transaction;
                    if ($data->status == AccountTransaction::OUT) {
                        $j->credit = $old_item->nominal_transaction;
                        $j->debit = 0;
                    } else {
                        $j->debit = $old_item->nominal_transaction;
                        $j->credit = 0;
                    }
                    $j->save();
                }
                return $old_item;
            }
        });
    }

    public function deleteTransaction(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $transaction = AccountTransaction::findOrFail($id);
            
            // Logic for rollback/delete
            $log_payment = LogPayment::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id) {
                $q->where('id', $id);
            })->first();

            if ($log_payment) {
                CustomVoid::rollbackPayment(AccountTransaction::class, $id);
            } else {
                JournalEntry::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id) {
                    $q->where('id', $id);
                })->delete();
                $transaction->delete();
            }

            return true;
        });
    }

    public function transferBalance(\App\DTOs\CastAccount\TransferBalanceData $data): AccountTransaction
    {
        return DB::transaction(function () use ($data) {
            $castAccount = CastAccount::where('id', $data->cast_account_id)->first();
            $balance = CustomHelper::total_balance_cast_account($data->cast_account_id, CastAccount::CASH);

            $date_transfer = $data->date_move_balance ?: Carbon::now()->format('Y-m-d');
            
            $log_payment = [];
            $old_saldo = $balance;
            $new_saldo = $balance - $data->nominal_transfer;
            $description = $data->description;

            $isAccount = strpos($data->to_account, 'acc_') === 0;
            $target_id = str_replace('acc_', '', $data->to_account);

            $newTransaction = new AccountTransaction;
            $newTransaction->cast_account_id = $data->cast_account_id;
            if ($isAccount) {
                $newTransaction->account_id = $target_id;
            } else {
                $newTransaction->cast_account_destination_id = $target_id;
            }
            $newTransaction->date_transaction = $date_transfer;
            $newTransaction->nominal_transaction = $data->nominal_transfer;
            $newTransaction->total_saldo_before = $old_saldo;
            $newTransaction->total_saldo_after = $new_saldo;
            $newTransaction->status = 'out';
            $newTransaction->description = $description;
            $newTransaction->save();

            $castAccount->total_saldo = $new_saldo;
            $castAccount->save();

            $log_payment[] = [
                'id' => $newTransaction->id,
                'type' => AccountTransaction::class,
                'reference_id' => $newTransaction->id,
                'reference_type' => AccountTransaction::class,
            ];

            // kurangi saldo utama
            $journal_balance = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $castAccount->account_id,
                'reference_id' => $newTransaction->id,
                'reference_type' => AccountTransaction::class,
                'description' => $description,
                'date' => Carbon::parse($date_transfer),
                'debit' => 0,
                'credit' => $data->nominal_transfer,
            ], [
                'account_id' => $castAccount->account_id,
                'reference_id' => $newTransaction->id,
                'reference_type' => AccountTransaction::class,
            ]);

            $log_payment[] = [
                'id' => $journal_balance->id,
                'type' => JournalEntry::class,
                'reference_id' => $newTransaction->id,
                'reference_type' => AccountTransaction::class,
            ];

            if (!$isAccount) {
                // other account
                $otherAccount = CastAccount::where('id', $data->to_account)->first();
                $other_old_saldo = CustomHelper::total_balance_cast_account($otherAccount->id, CastAccount::CASH);
                $other_new_saldo = $other_old_saldo + $data->nominal_transfer;

                $newTransaction_2 = new AccountTransaction;
                $newTransaction_2->cast_account_id = $otherAccount->id;
                $newTransaction_2->cast_account_destination_id = $newTransaction->cast_account_id;
                $newTransaction_2->date_transaction = $date_transfer;
                $newTransaction_2->nominal_transaction = $data->nominal_transfer;
                $newTransaction_2->total_saldo_before = $other_old_saldo;
                $newTransaction_2->total_saldo_after = $other_new_saldo;
                $newTransaction_2->status = 'enter';
                $newTransaction_2->description = $description;
                $newTransaction_2->save();

                $otherAccount->total_saldo = $other_new_saldo;
                $otherAccount->save();

                $log_payment[] = [
                    'id' => $newTransaction_2->id,
                    'type' => AccountTransaction::class,
                    'reference_id' => $newTransaction->id,
                    'reference_type' => AccountTransaction::class,
                ];
            } else {
                $otherAccount = \App\Models\Account::where('id', $target_id)->first();
                $otherAccount->account_id = $target_id;
                $newTransaction_2 = $newTransaction;
            }

            // tambah saldo di akun tujuan
            $journal_destination = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $otherAccount->account_id,
                'reference_id' => $newTransaction_2->id,
                'reference_type' => AccountTransaction::class,
                'description' => $description,
                'date' => Carbon::parse($date_transfer),
                'debit' => $data->nominal_transfer,
                'credit' => 0,
            ], [
                'account_id' => $otherAccount->account_id,
                'reference_id' => $newTransaction_2->id,
                'reference_type' => AccountTransaction::class,
            ]);

            $log_payment[] = [
                'id' => $journal_destination->id,
                'type' => JournalEntry::class,
                'reference_id' => $newTransaction->id,
                'reference_type' => AccountTransaction::class,
            ];

            if (sizeof($log_payment) > 0) {
                $newLogPayment = new LogPayment;
                $newLogPayment->reference_type = AccountTransaction::class;
                $newLogPayment->reference_id = $newTransaction->id;
                $newLogPayment->name = "CREATE_TRANSACTION";
                $newLogPayment->snapshot = json_encode($log_payment);
                $newLogPayment->save();
                if (!$isAccount) {
                    $newLogPayment = new LogPayment;
                    $newLogPayment->reference_type = AccountTransaction::class;
                    $newLogPayment->reference_id = $newTransaction_2->id;
                    $newLogPayment->name = "CREATE_TRANSACTION";
                    $newLogPayment->snapshot = json_encode($log_payment);
                    $newLogPayment->save();
                }
            }

            return $newTransaction_2;
        });
    }
}
