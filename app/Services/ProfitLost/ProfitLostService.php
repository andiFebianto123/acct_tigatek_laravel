<?php

namespace App\Services\ProfitLost;

use App\Models\ProjectProfitLost;
use App\Models\ProjectProfitLostLog;
use App\Models\ConsolidateIncomeItem;
use App\Models\ClientPo;
use App\Models\InvoiceClient;
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
            $item->orderable_id = $dto->orderable_id;
            $item->orderable_type = $dto->orderable_type;
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

            if ($dto->orderable_type === 'App\Models\ClientPo') {
                $po = ClientPo::findOrFail($dto->orderable_id);
                $po->price_after_year = $item->price_after_year;
                $po->price_total = $item->price_total;
                $po->profit_and_loss = $item->price_profit_lost_po;
                $po->load_general_value = $item->price_general;
                $po->profit_and_lost_final = $item->price_prift_lost_final;
                $po->category = $item->category;
                $po->save();
            }

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

            if ($project_profit_lost->orderable_id != $dto->orderable_id || $project_profit_lost->orderable_type != $dto->orderable_type) {
                $flag_update++;
                $project_profit_lost->orderable_id = $dto->orderable_id;
                $project_profit_lost->orderable_type = $dto->orderable_type;
                $project_profit_lost->client_po_id = $dto->orderable_type === 'App\Models\ClientPo' ? $dto->orderable_id : null;
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

            if ($project_profit_lost->orderable_type === 'App\\Models\\ClientPo') {
                $po = ClientPo::find($project_profit_lost->orderable_id);
                if ($po) {
                    $po->price_after_year = $project_profit_lost->price_after_year;
                    $po->price_total = $project_profit_lost->price_total;
                    $po->profit_and_loss = $project_profit_lost->price_profit_lost_po;
                    $po->load_general_value = $project_profit_lost->price_general;
                    $po->profit_and_lost_final = $project_profit_lost->price_prift_lost_final;
                    $po->category = $project_profit_lost->category;
                    $po->save();
                }
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

    /**
     * Otomatis membuat atau menyinkronkan data Laba Rugi dari Invoice Client.
     * Mendukung invoice penjualan barang (DeviceStock) dan invoice proyek (ClientPo).
     */
    public function syncProfitLostFromInvoice(InvoiceClient $invoice): ?ProjectProfitLost
    {
        return DB::transaction(function () use ($invoice) {
            $isDeviceSale = ($invoice->type_device === 'App\\Models\\DeviceStock' || $invoice->type_device === \App\Models\DeviceStock::class);

            if ($isDeviceSale) {
                // Skenario 1: Invoice Penjualan Barang (FIFO)
                $profitLost = ProjectProfitLost::where('orderable_type', 'App\\Models\\InvoiceClient')
                    ->where('orderable_id', $invoice->id)
                    ->first();

                if (!$profitLost) {
                    $profitLost = new ProjectProfitLost();
                    $profitLost->orderable_type = 'App\\Models\\InvoiceClient';
                    $profitLost->orderable_id = $invoice->id;
                    $profitLost->client_po_id = null;
                    $profitLost->voucher_id = null;
                    $profitLost->price_after_year = 0;
                    $profitLost->price_voucher = 0;
                    $profitLost->price_small_cash = 0;
                    $profitLost->price_total = 0;
                    $profitLost->price_profit_lost_po = 0;
                    $profitLost->price_general = 0;
                    $profitLost->price_prift_lost_final = 0;
                    $profitLost->contract_value = 0;
                    $profitLost->total_project = 0;
                }

                $profitLost->company_id = $invoice->company_id;
                $profitLost->category = strtoupper($invoice->category ?? 'RUTIN');
                $profitLost->save();

                return $profitLost;
            }

            if (!empty($invoice->client_po_id)) {
                // Skenario 2: Invoice Proyek / Subkon (ClientPo)
                $po = ClientPo::find($invoice->client_po_id);
                if (!$po) {
                    return null;
                }

                $profitLost = ProjectProfitLost::where('orderable_type', 'App\\Models\\ClientPo')
                    ->where('orderable_id', $po->id)
                    ->first();

                if (!$profitLost) {
                    // Cari voucher pertama dan akumulasi biaya voucher yang terhubung dengan PO ini
                    $voucherQuery = \App\Models\Voucher::where('client_po_id', $po->id);
                    $firstVoucher = (clone $voucherQuery)->first();
                    $totalVoucherBiaya = (float) (clone $voucherQuery)->sum('total');

                    $priceAfterYear = (float) ($po->price_after_year ?? 0);
                    $priceVoucher = $totalVoucherBiaya;
                    $priceSmallCash = 0;
                    $priceTotal = $priceAfterYear + $priceVoucher + $priceSmallCash;

                    $jobValueExcludePpn = (float) ($po->job_value ?? 0);
                    $priceProfitLostPo = $jobValueExcludePpn - $priceTotal;
                    $priceGeneral = (float) ($po->load_general_value ?? 0);
                    $priceProfitLostFinal = $priceProfitLostPo - $priceGeneral;

                    $profitLost = new ProjectProfitLost();
                    $profitLost->orderable_type = 'App\\Models\\ClientPo';
                    $profitLost->orderable_id = $po->id;
                    $profitLost->client_po_id = $po->id;
                    $profitLost->voucher_id = $firstVoucher?->id;
                    $profitLost->price_after_year = $priceAfterYear;
                    $profitLost->price_voucher = $priceVoucher;
                    $profitLost->price_small_cash = $priceSmallCash;
                    $profitLost->price_total = $priceTotal;
                    $profitLost->price_profit_lost_po = $priceProfitLostPo;
                    $profitLost->price_general = $priceGeneral;
                    $profitLost->price_prift_lost_final = $priceProfitLostFinal;
                    $profitLost->contract_value = 0;
                    $profitLost->total_project = 0;
                }

                $profitLost->company_id = $invoice->company_id ?? $po->company_id;
                $profitLost->category = strtoupper($invoice->category ?? $po->category ?? 'RUTIN');
                $profitLost->save();

                // Sinkronkan ke ClientPo
                $po->price_after_year = $profitLost->price_after_year;
                $po->price_total = $profitLost->price_total;
                $po->profit_and_loss = $profitLost->price_profit_lost_po;
                $po->load_general_value = $profitLost->price_general;
                $po->profit_and_lost_final = $profitLost->price_prift_lost_final;
                $po->category = $profitLost->category;
                $po->save();

                return $profitLost;
            }

            return null;
        });
    }

    /**
     * Menghapus data Laba Rugi yang terhubung saat invoice dihapus.
     */
    public function deleteProfitLostFromInvoice(InvoiceClient $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            // Hapus laba rugi jika invoice bertipe penjualan barang
            ProjectProfitLost::where('orderable_type', 'App\\Models\\InvoiceClient')
                ->where('orderable_id', $invoice->id)
                ->delete();

            // Jika invoice proyek dan tidak ada invoice lain untuk Client PO ini, hapus data laba rugi proyeknya
            if (!empty($invoice->client_po_id)) {
                $otherInvoicesCount = InvoiceClient::where('client_po_id', $invoice->client_po_id)
                    ->where('id', '!=', $invoice->id)
                    ->count();

                if ($otherInvoicesCount === 0) {
                    ProjectProfitLost::where('orderable_type', 'App\\Models\\ClientPo')
                        ->where('orderable_id', $invoice->client_po_id)
                        ->delete();
                }
            }
        });
    }
}
