<?php

namespace App\Services\Coa;

use App\Models\Account;
use App\Models\CastAccount;
use App\Models\AccountTransaction;
use App\Models\JournalEntry;
use App\DTOs\Coa\CoaSaveData;
use App\DTOs\CastAccount\TransactionSaveData;
use App\Services\CastAccount\CastAccountService;
use App\Http\Helpers\CustomHelper;
use App\Repositories\Coa\CoaRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CoaService
{
    public function __construct(
        protected CoaRepository $repository,
        protected CastAccountService $castAccountService
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
            $item->currency_code = $dto->currency_code ?? 'IDR';
            $item->save();

            $currencyCode = $dto->currency_code ?? 'IDR';
            $exchangeRate = 1.0;
            if ($currencyCode === 'USD') {
                $setting = \App\Models\Setting::first();
                $exchangeRate = (float) ($setting?->usd_rate ?? 16000);
            }

            $debit = (float) ($dto->balance ?? 0);
            $debitBase = $debit * $exchangeRate;

            CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $item->id,
                'reference_id' => $item->id,
                'reference_type' => Account::class,
                'description' => 'FIRST BALANCE',
                'date' => Carbon::now(),
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'debit' => $debit,
                'credit' => 0,
                'debit_base' => $debitBase,
                'credit_base' => 0,
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
            if ($dto->currency_code) {
                $item->currency_code = $dto->currency_code;
            }
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

            // Cek apakah akun COA ini terhubung ke CastAccount (kas/bank)
            $castAccount = CastAccount::where('account_id', $item->id)->first();

            if ($castAccount) {
                // Buat DTO transaksi masuk di CastAccount
                $transDto = new TransactionSaveData(
                    id: null,
                    cast_account_id: $castAccount->id,
                    date_transaction: Carbon::now()->format('Y-m-d'),
                    status: AccountTransaction::ENTER,
                    nominal_transaction: $dto->balance,
                    kdp: null,
                    job_name: null,
                    no_invoice: null,
                    account_id: $item->id,
                    description: 'FIRST BALANCE'
                );

                $this->castAccountService->storeTransaction($transDto);
            } else {
                $currencyCode = $item->currency_code ?? 'IDR';
                $exchangeRate = 1.0;
                if ($currencyCode === 'USD') {
                    $setting = \App\Models\Setting::first();
                    $exchangeRate = (float) ($setting?->usd_rate ?? 16000);
                }

                $debit = (float) ($dto->balance ?? 0);
                $debitBase = $debit * $exchangeRate;

                CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $item->id,
                    'reference_id' => $item->id,
                    'reference_type' => Account::class,
                    'description' => 'FIRST BALANCE',
                    'date' => Carbon::now(),
                    'currency_code' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'debit' => $debit,
                    'credit' => 0,
                    'debit_base' => $debitBase,
                    'credit_base' => 0,
                ], [
                    'account_id' => $item->id,
                    'reference_id' => $item->id,
                    'reference_type' => Account::class,
                ]);
            }

            $rootParent = $this->repository->findRootParent($item->code);
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
