<?php

namespace App\Repositories\Coa;

use App\Models\Account;
use App\DTOs\Coa\CoaFilterData;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;

class CoaRepository
{
    public function applyListQuery($query, CoaFilterData $dto): void
    {
        $startDate = $dto->startDate;
        $endDate = $dto->endDate;

        $netProfit = CustomHelper::getNetProfit($startDate, $endDate);
        
        $balanceSubquery = "(SELECT IFNULL(SUM(je.debit - je.credit), 0) FROM journal_entries je JOIN accounts a2 ON je.account_id = a2.id WHERE a2.code LIKE CONCAT(accounts.code, '%')";
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
                CASE WHEN accounts.code = '303' THEN $netProfit ELSE $balanceSubquery END as balance
            ")
        ]);

        if ($dto->id) {
            $parentAccount = Account::find($dto->id);
            if ($parentAccount) {
                if ($parentAccount->level == 1) {
                    $query->where('code', 'LIKE', "{$parentAccount->code}");
                } else {
                    $query->where('code', 'LIKE', "{$parentAccount->code}%");
                }
            }
        }

        $query->orderBy('code', 'asc');
    }

    public function findRootParent(string $code): ?Account
    {
        $parent = null;
        for ($i = 1; $i < strlen($code); $i++) {
            $prefix = substr($code, 0, $i);
            $account = Account::where('code', $prefix)
                ->whereIn('level', [1, 2])->first();
            if ($account) {
                $parent = $account;
            }
        }
        return $parent;
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

    public function getEntryForEdit(int $id)
    {
        return Account::leftJoin('journal_entries', 'journal_entries.account_id', '=', 'accounts.id')
            ->select(DB::raw("
                accounts.id as id,
                MAX(accounts.code) as code,
                MAX(accounts.name) as name,
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

    public function getLedgerQuery(Account $account, ?string $startDate, ?string $endDate)
    {
        $query = \App\Models\JournalEntry::whereHas('account', function ($q) use ($account) {
            $q->where('code', 'LIKE', $account->code . '%');
        });

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query;
    }

    public function getInitialBalance(Account $account, ?string $startDate): float
    {
        $query = \App\Models\JournalEntry::whereHas('account', function ($q) use ($account) {
            $q->where('code', 'LIKE', $account->code . '%');
        })->where(function ($q) use ($startDate) {
            if ($startDate) {
                $q->where('date', '<', $startDate);
            }
        });

        $result = $query->selectRaw('IFNULL(SUM(debit) - SUM(credit), 0) as balance')->first();
        return (float) ($result->balance ?? 0);
    }

    public function getCumulativeBalanceBeforeEntry(Account $account, \App\Models\JournalEntry $firstEntry): float
    {
        return \App\Models\JournalEntry::whereHas('account', function ($q) use ($account) {
            $q->where('code', 'LIKE', $account->code . '%');
        })
            ->where(function ($q) use ($firstEntry) {
                $q->where('date', '<', $firstEntry->date)
                    ->orWhere(function ($sq) use ($firstEntry) {
                        $sq->where('date', $firstEntry->date)->where('id', '<', $firstEntry->id);
                    });
            })->selectRaw('SUM(debit) - SUM(credit) as balance')->first()->balance ?? 0;
    }
}
