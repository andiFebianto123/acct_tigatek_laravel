<?php

namespace App\Repositories\CastAccount;

use App\Models\CastAccount;
use App\Models\AccountTransaction;
use App\DTOs\CastAccount\CastAccountFilterData;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CastAccountRepository
{
    public function getCastAccountsWithBalance(CastAccountFilterData $filters): Collection
    {
        $query = CastAccount::leftJoin('account_transactions', 'account_transactions.cast_account_id', '=', 'cast_accounts.id')
            ->where('cast_accounts.status', CastAccount::CASH)
            ->groupBy('cast_accounts.id');

        if ($filters->year) {
            $query->whereYear('account_transactions.date_transaction', $filters->year);
        }

        $order = $filters->order ?: 'ASC';

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
        ->orderBy('cast_accounts.id', $order)
        ->get();
    }

    public function findById(int $id): ?CastAccount
    {
        return CastAccount::find($id);
    }

    public function getTransactionQuery(int $id, ?string $year = null)
    {
        $query = AccountTransaction::leftJoin('log_payments', function ($q) {
            $q->on('account_transactions.id', 'log_payments.reference_id')
                ->where('log_payments.reference_type', AccountTransaction::class);
        })
            ->where('account_transactions.cast_account_id', $id)
            ->where('account_transactions.is_first', 0);

        if ($year && $year != 'all') {
            $query->whereYear('account_transactions.date_transaction', $year);
        }

        $query->leftJoin('invoice_clients', function ($join) {
            $join->on('account_transactions.reference_id', 'invoice_clients.id')
                ->where('account_transactions.reference_type', 'App\\Models\\InvoiceClient');
        });

        $query->leftJoin('vouchers', function ($join) {
            $join->on('account_transactions.reference_id', 'vouchers.id')
                ->where('account_transactions.reference_type', 'App\\Models\\Voucher');
        });

        $query->leftJoin('invoice_clients as voucher_invoice', function ($join) {
            $join->on('vouchers.client_po_id', 'voucher_invoice.client_po_id');
        });

        $query->leftJoin('accounts as voucher_account', 'vouchers.account_id', '=', 'voucher_account.id');
        $query->leftJoin('cast_accounts as trans_cast_account', 'account_transactions.cast_account_id', '=', 'trans_cast_account.id');
        $query->leftJoin('accounts as trans_account', 'trans_cast_account.account_id', '=', 'trans_account.id');

        $query->select([
            'account_transactions.*',
            'log_payments.id as log_payment_id',
            'invoice_clients.invoice_number as invoice_number',
            'invoice_clients.kdp as invoice_kdp',
            'vouchers.work_code as voucher_kdp',
            'vouchers.payment_description as voucher_description',
            'voucher_account.code as voucher_account_code',
            'voucher_account.name as voucher_account_name',
            'trans_account.code as trans_account_code',
            'trans_account.name as trans_account_name',
            'voucher_invoice.invoice_number as indirect_invoice_number'
        ]);

        return $query;
    }

    public function getTransactionsByCastAccountId(int $id, ?string $year = null)
    {
        return $this->getTransactionQuery($id, $year)->orderBy('date_transaction', 'ASC')->get();
    }
}
