<?php

namespace App\Repositories\CastAccountLoan;

use App\Models\CastAccount;
use App\Models\AccountTransaction;
use App\Models\LoanTransactionFlag;
use App\DTOs\CastAccountLoan\CastAccountLoanFilterData;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CastAccountLoanRepository
{
    public function getLoanAccountsWithBalance(CastAccountLoanFilterData $filters): Collection
    {
        $query = CastAccount::leftJoin('account_transactions', 'account_transactions.cast_account_id', '=', 'cast_accounts.id')
            ->where('cast_accounts.status', CastAccount::LOAN)
            ->groupBy('cast_accounts.id');

        return $query->select(DB::raw('
            cast_accounts.id,
            MAX(cast_accounts.name) as name,
            MAX(cast_accounts.bank_name) as bank_name,
            MAX(cast_accounts.no_account) as no_account,
            MAX(cast_accounts.status) as status,
            MAX(cast_accounts.account_id) as account_id,
            SUM(IF(account_transactions.status = "enter", account_transactions.nominal_transaction, 0)) as total_saldo_enter,
            SUM(IF(account_transactions.status = "out", account_transactions.nominal_transaction, 0)) as total_saldo_out
        '))
        ->orderBy('cast_accounts.id', $filters->order ?: 'ASC')
        ->get();
    }

    public function findById(int $id): ?CastAccount
    {
        return CastAccount::find($id);
    }

    public function getTransactionQuery(int $id, ?string $year = null)
    {
        $query = AccountTransaction::select([
            'account_transactions.id',
            'account_transactions.cast_account_id',
            'loan_transaction_flags.kode as kode',
            'account_transactions.date_transaction',
            'account_transactions.total_saldo_after as loan_remaining',
            'account_transactions.nominal_transaction',
            'account_transactions.status',
            'account_transactions.description',
            'cast_accounts.name as destination_name'
        ])
        ->leftJoin('loan_transaction_flags', function($join) {
            $join->on('account_transactions.reference_id', '=', 'loan_transaction_flags.id')
                 ->where('account_transactions.reference_type', '=', LoanTransactionFlag::class);
        })
        ->leftJoin('cast_accounts', 'account_transactions.cast_account_destination_id', '=', 'cast_accounts.id')
        ->where('account_transactions.cast_account_id', $id)
        ->where('account_transactions.is_first', 0);

        if ($year && $year != 'all') {
            $query->whereYear('account_transactions.date_transaction', $year);
        }

        return $query->orderBy('account_transactions.date_transaction', 'ASC');
    }

    public function findLoanTransactionFlagById(int $id): ?LoanTransactionFlag
    {
        return LoanTransactionFlag::find($id);
    }

    public function getTotalLoanOutByFlagId(int $flagId): float
    {
        return (float) AccountTransaction::where("reference_id", $flagId)
            ->whereNull('cast_account_destination_id')
            ->where("reference_type", LoanTransactionFlag::class)
            ->where('status', CastAccount::OUT)
            ->sum('nominal_transaction');
    }

    public function getFirstAccountTransactionByFlagId(int $flagId): ?AccountTransaction
    {
        return AccountTransaction::where("reference_id", $flagId)
            ->where("reference_type", LoanTransactionFlag::class)
            ->first();
    }
}
