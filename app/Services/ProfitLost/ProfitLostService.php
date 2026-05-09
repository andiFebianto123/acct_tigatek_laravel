<?php

namespace App\Services\ProfitLost;

use App\Models\ProjectProfitLost;
use App\Models\ProjectProfitLostLog;
use App\Models\ConsolidateIncomeItem;
use App\Models\ClientPo;
use App\DTOs\ProfitLost\ProjectProfitLostSaveData;
use App\DTOs\ProfitLost\ConsolidateItemSaveData;
use Illuminate\Support\Facades\DB;

class ProfitLostService
{
    public function storeConsolidateItem(ConsolidateItemSaveData $dto): ConsolidateIncomeItem
    {
        return DB::transaction(function () use ($dto) {
            $item = new ConsolidateIncomeItem();
            $item->header_id = $dto->header_id;
            $item->account_id = $dto->account_id;
            $item->save();

            return $item;
        });
    }

    public function storeProjectProfitLost(ProjectProfitLostSaveData $dto): ProjectProfitLost
    {
        return DB::transaction(function () use ($dto) {
            $item = new ProjectProfitLost();
            $item->voucher_id = $dto->voucher_id;
            $item->client_po_id = $dto->client_po_id;
            $item->price_after_year = $dto->price_after_year;
            $item->price_voucher = $dto->price_voucher;
            $item->price_small_cash = $dto->price_small_cash;
            $item->price_total = $dto->price_total;
            $item->price_profit_lost_po = $dto->price_profit_lost_po;
            $item->price_general = $dto->price_general;
            $item->price_prift_lost_final = $dto->price_prift_lost_final;
            $item->category = $dto->category;
            $item->company_id = $dto->company_id;
            $item->contract_value = 0;
            $item->total_project = 0;
            $item->save();

            $po = ClientPo::findOrFail($dto->client_po_id);
            $po->price_after_year = $item->price_after_year;
            $po->price_total = $item->price_total;
            $po->profit_and_loss = $item->price_profit_lost_po;
            $po->load_general_value = $item->price_general;
            $po->profit_and_lost_final = $item->price_prift_lost_final;
            $po->category = $item->category;
            $po->save();

            return $item;
        });
    }

    public function updateProjectProfitLost(ProjectProfitLostSaveData $dto): ProjectProfitLost
    {
        return DB::transaction(function () use ($dto) {
            $project_profit_lost = ProjectProfitLost::findOrFail($dto->id);
            
            $flag_update = 0;

            if ($project_profit_lost->price_small_cash != $dto->price_small_cash) {
                $flag_update++;
                $project_profit_lost->price_small_cash = $dto->price_small_cash;
            }

            if ($project_profit_lost->price_after_year != $dto->price_after_year) {
                $flag_update++;
                $project_profit_lost->price_after_year = $dto->price_after_year;
            }

            if ($project_profit_lost->price_general != $dto->price_general) {
                $flag_update++;
                $project_profit_lost->price_general = $dto->price_general;
            }

            if ($project_profit_lost->company_id != $dto->company_id) {
                $flag_update++;
                $project_profit_lost->company_id = $dto->company_id;
            }

            if ($flag_update > 0) {
                $project_profit_lost->save();

                $new_profit_log = new ProjectProfitLostLog();
                $new_profit_log->project_profit_lost_id = $project_profit_lost->id;
                $new_profit_log->user_id = backpack_user()->id;
                $new_profit_log->price_after_year = $dto->price_after_year;
                $new_profit_log->price_general = $dto->price_general;
                $new_profit_log->save();
            }

            return $project_profit_lost;
        });
    }

    public function deleteProjectProfitLost(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = ProjectProfitLost::findOrFail($id);
            return $item->delete();
        });
    }

    public function deleteConsolidateItem(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = ConsolidateIncomeItem::findOrFail($id);
            return $item->delete();
        });
    }

    public function updateConsolidateItem(int $id, ConsolidateItemSaveData $dto): ConsolidateIncomeItem
    {
        return DB::transaction(function () use ($id, $dto) {
            $item = ConsolidateIncomeItem::findOrFail($id);
            $item->header_id = $dto->header_id;
            $item->account_id = $dto->account_id;
            $item->save();

            return $item;
        });
    }
}
