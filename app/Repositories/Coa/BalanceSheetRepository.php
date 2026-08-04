<?php

namespace App\Repositories\Coa;

use App\Models\Account;
use App\Models\JournalEntry;
use App\DTOs\Coa\BalanceSheetFilterData;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;

class BalanceSheetRepository
{
    public function getJournalYears(): array
    {
        return JournalEntry::selectRaw('YEAR(date) as year')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    public function getFirstAccountByType(string $type, array $levels = [2]): ?Account
    {
        return Account::where('type', $type)
            ->whereIn('level', $levels)
            ->where('is_active', 1)
            ->orderBy('code', 'asc')
            ->first();
    }

    public function applyBalanceSheetQuery($query, BalanceSheetFilterData $filter): void
    {
        $startDate = $filter->startDate;
        $endDate = $filter->endDate;
        $type = $filter->type;

        $netProfit = CustomHelper::getNetProfit($startDate, $endDate);
        
        $balanceSubquery = "(SELECT CASE WHEN accounts.currency_code = 'USD' THEN IFNULL(SUM(je.debit - je.credit), 0) ELSE IFNULL(SUM(je.debit_base - je.credit_base), 0) END FROM journal_entries je JOIN accounts a2 ON je.account_id = a2.id WHERE a2.code LIKE CONCAT(accounts.code, '%') AND a2.type = accounts.type";
        if ($startDate && $endDate) {
            $balanceSubquery .= " AND je.date BETWEEN '$startDate' AND '$endDate'";
        }
        $balanceSubquery .= ")";

        $query->select([
            DB::raw("
                accounts.id as id,
                accounts.id as id_,
                accounts.code as code_,
                accounts.name as name_,
                accounts.level as level_,
                accounts.currency_code as currency_code,
                CASE WHEN accounts.code = '303' THEN $netProfit ELSE $balanceSubquery END as balance
            ")
        ]);

        if ($type) {
            $query->where('accounts.type', $type);
        } else {
            $query->whereIn('accounts.type', ['Assets', 'Liabilities', 'Equity']);
        }

        $query->whereIn('accounts.level', [2, 3])
            ->orderBy('accounts.code', 'asc');

    }

    public function getTotals(BalanceSheetFilterData $filter): array
    {
        $startDate = $filter->startDate;
        $endDate = $filter->endDate;

        $netProfit = CustomHelper::getNetProfit($startDate, $endDate);

        $total_asset = Account::leftJoin('journal_entries', function ($q) use ($startDate, $endDate) {
            $q->on('accounts.id', '=', 'journal_entries.account_id');
            if ($startDate && $endDate) {
                $q->whereBetween('journal_entries.date', [$startDate, $endDate]);
            }
        })->where('accounts.type', 'Assets')
            ->where('accounts.code', '!=', '303')
            ->select(DB::raw('SUM(IFNULL(journal_entries.debit_base, journal_entries.debit) - IFNULL(journal_entries.credit_base, journal_entries.credit)) as balance'))
            ->first();

        $total_liabilities = Account::leftJoin('journal_entries', function ($q) use ($startDate, $endDate) {
            $q->on('accounts.id', '=', 'journal_entries.account_id');
            if ($startDate && $endDate) {
                $q->whereBetween('journal_entries.date', [$startDate, $endDate]);
            }
        })->where('accounts.type', 'Liabilities')
            ->where('accounts.code', '!=', '303')
            ->select(DB::raw('SUM(IFNULL(journal_entries.debit_base, journal_entries.debit) - IFNULL(journal_entries.credit_base, journal_entries.credit)) as balance'))
            ->first();

        $equity_base = Account::leftJoin('journal_entries', function ($q) use ($startDate, $endDate) {
            $q->on('accounts.id', '=', 'journal_entries.account_id');
            if ($startDate && $endDate) {
                $q->whereBetween('journal_entries.date', [$startDate, $endDate]);
            }
        })->where('accounts.type', 'Equity')
            ->where('accounts.code', '!=', '303')
            ->select(DB::raw('SUM(IFNULL(journal_entries.debit_base, journal_entries.debit) - IFNULL(journal_entries.credit_base, journal_entries.credit)) as balance'))
            ->first();

        return [
            'total_asset' => $total_asset->balance ?? 0,
            'total_liabilities' => $total_liabilities->balance ?? 0,
            'total_equity' => ($equity_base->balance ?? 0) + $netProfit,
        ];
    }

    public function getLedgerQuery(Account $account, BalanceSheetFilterData $filter)
    {
        $startDate = $filter->startDate;
        $endDate = $filter->endDate;

        $query = JournalEntry::whereHas('account', function ($q) use ($account) {
            $q->where('code', 'LIKE', $account->code . '%');
        });

        return $query;
    }

    public function getCumulativeBalanceBefore(Account $account, ?string $date, ?int $id = null): float
    {
        $query = JournalEntry::whereHas('account', function ($q) use ($account) {
            $q->where('code', 'LIKE', $account->code . '%');
        });

        if ($date) {
            $query->where(function ($q) use ($date, $id) {
                $q->where('date', '<', $date);
                if ($id) {
                    $q->orWhere(function ($sq) use ($date, $id) {
                        $sq->where('date', $date)->where('id', '<', $id);
                    });
                }
            });
        }

        if ($account->currency_code === 'USD') {
            return (float) ($query->selectRaw('SUM(debit) - SUM(credit) as balance')->first()->balance ?? 0);
        } else {
            return (float) ($query->selectRaw('SUM(IFNULL(debit_base, debit)) - SUM(IFNULL(credit_base, credit)) as balance')->first()->balance ?? 0);
        }
    }

    public function findParentByCode(string $code): ?Account
    {
        $parent = null;
        for ($i = 1; $i < strlen($code); $i++) {
            $prefix = substr($code, 0, $i);
            $account = Account::where('code', $prefix)->first();
            if ($account) {
                $parent = $account;
            }
        }
        return $parent;
    }

    public function findRootParent(string $code): ?Account
    {
        $parent = null;
        for ($i = 1; $i <= strlen($code); $i++) {
            $prefix = substr($code, 0, $i);
            $account = Account::where('code', $prefix)
                ->whereIn('level', [1, 2])->first();
            if ($account) {
                $parent = $account;
            }
        }
        return $parent;
    }

    public function getEntryWithBalance(int $id)
    {
        return Account::leftJoin('journal_entries', 'journal_entries.account_id', '=', 'accounts.id')
            ->select(DB::raw("
                accounts.id as id,
                MAX(accounts.code) as code,
                MAX(accounts.name) as name,
                MAX(accounts.type) as type,
                MAX(accounts.level) as level,
                (SUM(journal_entries.debit) - SUM(journal_entries.credit)) as balance
            "))->where('accounts.id', $id)
            ->groupBy('accounts.id')
            ->first();
    }

    public function hasChildren(Account $account): bool
    {
        return Account::where('code', 'LIKE', "{$account->code}%")
            ->where('id', '!=', $account->id)
            ->exists();
    }
}
