<?php

namespace App\Services\Coa;

use App\Models\Account;
use App\Models\JournalEntry;
use App\DTOs\Coa\CoaSaveData;
use App\Http\Helpers\CustomHelper;
use App\Repositories\Coa\CoaRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CoaService
{
    public function __construct(
        protected CoaRepository $repository
    ) {}

    public function store(CoaSaveData $dto): Account
    {
        return DB::transaction(function () use ($dto) {
            $beforeAccount = $this->repository->findParentByCode($dto->code);
            $rootParent = $this->repository->findRootParent($dto->code);

            $level = ($beforeAccount) ? $beforeAccount->level + 1 : 2;

            $item = new Account();
            $item->code = $dto->code;
            $item->name = $dto->name;
            $item->type = Account::EXPENSE;
            $item->level = $level;
            $item->save();

            CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $item->id,
                'reference_id' => $item->id,
                'reference_type' => Account::class,
                'description' => 'FIRST BALANCE',
                'date' => Carbon::now(),
                'debit' => $dto->balance,
            ], [
                'reference_id' => $item->id,
                'reference_type' => Account::class,
            ]);

            if ($rootParent) {
                $item->component_name = 'account_' . $rootParent->id;
            }

            return $item;
        });
    }

    public function update(CoaSaveData $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $item = Account::findOrFail($dto->id);
            $oldCode = $item->code;
            
            $rootParentBefore = $this->repository->findRootParent($oldCode);

            $newParent = $this->repository->findParentByCode($dto->code);

            $item->code = $dto->code;
            $item->name = $dto->name;
            if ($newParent && $newParent->code != $item->code) {
                $item->level = $newParent->level + 1;
            }
            $item->save();

            $rootParentAfter = $this->repository->findRootParent($item->code);

            $events = [];
            if ($rootParentBefore) {
                $events['account_' . $rootParentBefore->id . '_update_success'] = true;
            }
            if ($rootParentAfter) {
                $events['account_' . $rootParentAfter->id . '_update_success'] = true;
            }

            return [
                'item' => $item,
                'events' => $events
            ];
        });
    }

    public function addBalance(CoaSaveData $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $item = Account::findOrFail($dto->id);

            CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $item->id,
                'reference_id' => $item->id,
                'reference_type' => Account::class,
                'description' => 'FIRST BALANCE',
                'date' => Carbon::now(),
                'debit' => $dto->balance,
            ], [
                'reference_id' => $item->id,
                'reference_type' => Account::class,
            ]);


            $rootParent = $this->repository->findRootParent($item->code);
            dd($rootParent);
            $events = [];
            if ($rootParent) {
                $events['account_' . $rootParent->id . '_update_success'] = true;
            }

            return [
                'item' => $item,
                'events' => $events
            ];
        });
    }

    public function destroy(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $item = Account::findOrFail($id);
            
            if ($this->repository->hasChildren($item)) {
                throw new \Exception(trans('backpack::crud.expense_account.field.code.errors.delete'));
            }

            $parentAccount = $this->repository->findRootParent($item->code);
            $events = [];

            if ($parentAccount) {
                $events['account_' . $parentAccount->id . '_update_success'] = true;
            }

            JournalEntry::where('account_id', $item->id)->delete();
            $item->delete();

            return $events;
        });
    }
}
