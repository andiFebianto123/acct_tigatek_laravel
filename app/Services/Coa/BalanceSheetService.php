<?php

namespace App\Services\Coa;

use App\Models\Account;
use App\Models\JournalEntry;
use App\DTOs\Coa\BalanceSheetSaveData;
use App\Http\Helpers\CustomHelper;
use App\Repositories\Coa\BalanceSheetRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    public function __construct(
        protected BalanceSheetRepository $repository
    ) {}

    public function store(BalanceSheetSaveData $dto): Account
    {
        return DB::transaction(function () use ($dto) {
            $beforeAccount = $this->repository->findParentByCode($dto->code);
            $rootParent = $this->repository->findRootParent($dto->code);

            $level = ($beforeAccount) ? $beforeAccount->level + 1 : 2;

            $item = new Account();
            $item->code = $dto->code;
            $item->name = $dto->name;
            $item->type = $dto->type;
            $item->level = $level;
            $item->save();

            CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $item->id,
                'reference_id' => $item->id,
                'reference_type' => Account::class,
                'description' => 'FIRST BALANCE',
                'date' => $dto->date ?? Carbon::now(),
                'debit' => $dto->balance,
            ], [
                'reference_id' => $item->id,
                'reference_type' => Account::class,
            ]);

            if ($rootParent) {
                $item->component_name = 'account_' . $rootParent->type;
            }

            return $item;
        });
    }

    public function update(BalanceSheetSaveData $dto): Account
    {
        return DB::transaction(function () use ($dto) {
            $item = Account::findOrFail($dto->id);
            
            $beforeAccount = $this->repository->findParentByCode($dto->code);
            $rootParent = $this->repository->findRootParent($dto->code);

            $item->code = $dto->code;
            $item->name = $dto->name;
            $item->type = $dto->type;
            if ($beforeAccount && $beforeAccount->code != $item->code) {
                $item->level = $beforeAccount->level + 1;
            }
            $item->save();

            if ($rootParent) {
                $item->component_name = 'account_' . $rootParent->type;
            }

            return $item;
        });
    }

    public function destroy(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = Account::findOrFail($id);
            
            if ($this->repository->hasChildren($item)) {
                throw new \Exception(trans('backpack::crud.expense_account.field.code.errors.delete'));
            }

            JournalEntry::where('account_id', $item->id)->delete();
            return $item->delete();
        });
    }
}
