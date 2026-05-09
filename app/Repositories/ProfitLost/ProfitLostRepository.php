<?php

namespace App\Repositories\ProfitLost;

use App\Models\Account;
use App\Models\ProjectProfitLost;
use App\Models\ConsolidateIncomeItem;
use App\Models\ClientPo;
use App\Models\Voucher;
use App\Http\Helpers\CustomHelper;
use App\DTOs\ProfitLost\ProfitLostFilterData;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfitLostRepository
{
    public function getConsolidatedFormula(ProfitLostFilterData $filter): array
    {
        $startDate = $filter->startDate;
        $endDate = $filter->endDate;

        $dataset = [];
        $consolidate_income_header = DB::table('consolidate_income_headers')
            ->orderBy('id', 'asc')->get();

        $contract_income_number = 0;

        // Header index 0: Pendapatan Kontrak
        if (isset($consolidate_income_header[0])) {
            $data = [
                'name' => $consolidate_income_header[0]->name,
                'total' => 0,
                'item' => [],
            ];
            $totalAll = 0;
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[0]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $totalAll += $total_account;
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['total'] = CustomHelper::formatRupiahWithCurrency($totalAll);
            $data['item'] = $items;
            $dataset[] = $data;
            $contract_income_number = $totalAll;
        }

        // Header index 1: Beban Usaha
        $cost_expense_number = 0;
        if (isset($consolidate_income_header[1])) {
            $data = [
                'name' => $consolidate_income_header[1]->name,
                'total' => 0,
                'item' => [],
            ];
            $totalAll = 0;
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[1]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $totalAll += $total_account;
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['item'] = $items;
            $data['total'] = CustomHelper::formatRupiahWithCurrency($totalAll);
            $dataset[] = $data;
            $cost_expense_number = $totalAll;
        }

        // Header index 2: Laba Usaha
        $cost_profit_expense_number = $contract_income_number - $cost_expense_number;
        if (isset($consolidate_income_header[2])) {
            $data = [
                'name' => $consolidate_income_header[2]->name,
                'total' => CustomHelper::formatRupiahWithCurrency($cost_profit_expense_number),
                'item' => [],
            ];
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[2]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['item'] = $items;
            $dataset[] = $data;
        }

        // Header index 3: Pendapatan Lain - lain
        $cost_other_number = 0;
        if (isset($consolidate_income_header[3])) {
            $data = [
                'name' => $consolidate_income_header[3]->name,
                'total' => 0,
                'item' => [],
            ];
            $totalAll = 0;
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[3]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $totalAll += $total_account;
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['total'] = CustomHelper::formatRupiahWithCurrency($totalAll);
            $data['item'] = $items;
            $dataset[] = $data;
            $cost_other_number = $totalAll;
        }

        // Header index 4: Beban Lain - lain
        $expense_other_number = 0;
        if (isset($consolidate_income_header[4])) {
            $data = [
                'name' => $consolidate_income_header[4]->name,
                'total' => 0,
                'item' => [],
            ];
            $totalAll = 0;
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[4]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $totalAll += $total_account;
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['total'] = CustomHelper::formatRupiahWithCurrency($totalAll);
            $data['item'] = $items;
            $dataset[] = $data;
            $expense_other_number = $totalAll;
        }

        // Header index 5: Laba Sebelum Pajak
        $profit_before_tax = $cost_profit_expense_number + $cost_other_number - $expense_other_number;
        if (isset($consolidate_income_header[5])) {
            $data = [
                'name' => $consolidate_income_header[5]->name,
                'total' => CustomHelper::formatRupiahWithCurrency($profit_before_tax),
                'item' => [],
            ];
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[5]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['item'] = $items;
            $dataset[] = $data;
        }

        // Header index 6: Beban Pajak
        $expense_tax_number = 0;
        if (isset($consolidate_income_header[6])) {
            $data = [
                'name' => $consolidate_income_header[6]->name,
                'total' => CustomHelper::formatRupiahWithCurrency(0),
                'item' => [],
            ];
            $totalAll = 0;
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[6]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $totalAll += $total_account;
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['total'] = CustomHelper::formatRupiahWithCurrency($totalAll);
            $data['item'] = $items;
            $dataset[] = $data;
            $expense_tax_number = $totalAll;
        }

        // Header index 7: Laba Bersih
        $net_profit = $profit_before_tax - $expense_tax_number;
        if (isset($consolidate_income_header[7])) {
            $data = [
                'name' => $consolidate_income_header[7]->name,
                'total' => CustomHelper::formatRupiahWithCurrency($net_profit),
                'item' => [],
            ];
            $items = ConsolidateIncomeItem::leftJoin('accounts', 'accounts.id', 'consolidate_income_account_items.account_id')
                ->where('consolidate_income_account_items.header_id', $consolidate_income_header[7]->id)
                ->select(DB::raw("accounts.*"))->get();
            foreach ($items as $item) {
                $total_account = CustomHelper::balanceAccount($item->code, $startDate, $endDate);
                $item->total = CustomHelper::formatRupiahWithCurrency($total_account);
            }
            $data['item'] = $items;
            $dataset[] = $data;
        }

        return $dataset;
    }

    public function getProjectProfitLostTotals(ProfitLostFilterData $filter): array
    {
        $category = $filter->category;
        $filter_year = $filter->year;

        $vouchers = DB::table('vouchers')
            ->select(
                'client_po_id',
                DB::raw('SUM(payment_transfer) AS payment_transfer'),
                DB::raw('SUM(total) AS voucher_biaya')
            )
            ->groupBy('client_po_id');

        $invoiceClients = DB::table('invoice_clients')
            ->select(
                'client_po_id',
                DB::raw("GROUP_CONCAT(invoice_date SEPARATOR ',') AS invoice_date"),
                DB::raw("SUM(price_total_exclude_ppn) AS price_job_exlude_ppn"),
                DB::raw("SUM(price_total_include_ppn) AS price_job_include_ppn")
            )
            ->when($filter_year && $filter_year != 'all', function ($query) use ($filter_year) {
                return $query->whereYear('invoice_date', $filter_year);
            })
            ->groupBy('client_po_id');

        $client_po_query_exclude_ppn = DB::table("client_po")
            ->leftJoinSub($invoiceClients, 'invoice', function ($join) {
                $join->on('invoice.client_po_id', '=', 'client_po.id');
            })->select(
                "client_po.id as client_po_id",
                "client_po.category",
                DB::raw("IF(invoice.invoice_date IS NULL, client_po.job_value, invoice.price_job_exlude_ppn) as price_job_exlude_ppn_logic"),
                DB::raw("IF(invoice.invoice_date IS NULL, 0, invoice.price_job_include_ppn) as job_value_include_ppn_logic")
            );

        $mainQuery = DB::table('project_profit_lost')
            ->select([
                DB::raw('
                    ((client_po.price_job_exlude_ppn_logic -
                        (IFNULL(project_profit_lost.price_after_year,0)
                        + IFNULL(vouchers_logic.voucher_biaya,0)
                        + IFNULL(project_profit_lost.price_small_cash,0)))
                    - IFNULL(project_profit_lost.price_general, 0)
                ) AS price_prift_lost_final_str')
            ])
            ->leftJoinSub($client_po_query_exclude_ppn, 'client_po', function ($join) {
                $join->on('client_po.client_po_id', '=', 'project_profit_lost.client_po_id');
            })
            ->leftJoinSub($vouchers, 'vouchers_logic', 'vouchers_logic.client_po_id', 'client_po.client_po_id');

        $total_excl_ppn_logic_query = DB::table('project_profit_lost')
            ->leftJoinSub($client_po_query_exclude_ppn, 'dummy_query', function ($join) {
                $join->on('dummy_query.client_po_id', '=', 'project_profit_lost.client_po_id');
            })
            ->select(
                DB::raw("SUM(dummy_query.price_job_exlude_ppn_logic) as total_excl_ppn_logic")
            );

        if ($category) {
            $mainQuery = $mainQuery->where('client_po.category', $category);
            $total_excl_ppn_logic_query = $total_excl_ppn_logic_query->where('dummy_query.category', $category);
        }

        $mainQuery = $mainQuery->whereExists(function ($query) use ($filter_year) {
            $query->select(DB::raw(1))
                ->from('invoice_clients')
                ->whereColumn('invoice_clients.client_po_id', 'project_profit_lost.client_po_id');
            if ($filter_year && $filter_year != 'all') {
                $query->whereYear('invoice_date', $filter_year);
            }
        });

        $total_excl_ppn_logic_query = $total_excl_ppn_logic_query->whereExists(function ($query) use ($filter_year) {
            $query->select(DB::raw(1))
                ->from('invoice_clients')
                ->whereColumn('invoice_clients.client_po_id', 'project_profit_lost.client_po_id');
            if ($filter_year && $filter_year != 'all') {
                $query->whereYear('invoice_date', $filter_year);
            }
        });

        $total_excl_ppn_logic = $total_excl_ppn_logic_query->first();
        $result = DB::query()
            ->fromSub($mainQuery, 't')
            ->selectRaw('SUM(t.price_prift_lost_final_str) AS total_price_prift_lost_final_str')
            ->first();

        return [
            'total_price_exlude_ppn' => CustomHelper::formatRupiahWithCurrency($total_excl_ppn_logic->total_excl_ppn_logic ?? 0),
            'total_price_prift_lost_finals' => CustomHelper::formatRupiahWithCurrency($result->total_price_prift_lost_final_str ?? 0)
        ];
    }

    public function getProjectDetail(int $id, ?string $filterYear = null, bool $pure = false): array
    {
        $profitLost = ProjectProfitLost::findOrFail($id);
        $po = $profitLost->clientPo;
        $account_material = Account::where('code', 50101)->first();

        $filter = [];
        if ($filterYear) {
            $filter['filter_year'] = $filterYear;
        }

        $profit_lost_all_price = CustomHelper::profitLostRepository($filter)
            ->where('project_profit_lost.client_po_id', $po->id)
            ->whereExists(function ($query) use ($filter) {
                $query->select(DB::raw(1))
                    ->from('invoice_clients')
                    ->whereColumn('invoice_clients.client_po_id', 'client_po.client_po_id');
                if (!empty($filter['filter_year'])) {
                    $query->whereYear('invoice_date', $filter['filter_year']);
                }
            })
            ->first();

        if (!$profit_lost_all_price) {
            return [];
        }

        $price_po_excl_ppn = (float) $profit_lost_all_price->price_job_exlude_ppn_logic;

        $material_data = (float) Voucher::where('client_po_id', $po->id)
            ->where('account_id', $account_material->id)
            ->select(DB::raw("SUM(total) as biaya"))->groupBy('client_po_id')->get()->sum('biaya');

        $account_subkon = Account::whereIn('code', [50102, 50103])->get()
            ->pluck('id')->toArray();

        $subkon_data = (float) Voucher::where('client_po_id', $po->id)
            ->whereIn('account_id', $account_subkon)
            ->select(DB::raw("SUM(total) as subkon"))->groupBy('client_po_id')->get()->sum('subkon');

        $account_btkl = Account::where('code', 50104)->first();

        $btkl_data = (float) Voucher::where('client_po_id', $po->id)
            ->where('account_id', $account_btkl->id)
            ->select(DB::raw("SUM(total) as btkl"))->groupBy('client_po_id')->get()->sum('btkl');

        $account_other = Account::whereIn('code', [50101, 50102, 50103, 50104])->get()
            ->pluck('id')->toArray();

        $price_other_data = (float) Voucher::where('client_po_id', $po->id)
            ->whereNotIn('account_id', $account_other)
            ->select(DB::raw("SUM(total) as price_other"))->groupBy('client_po_id')->get()->sum('price_other');

        $price_profit_lost = (float) $profitLost->price_after_year;
        $price_total = $material_data + $subkon_data + $btkl_data + $price_other_data + $price_profit_lost;
        $price_profit_lost_po = (float) $profit_lost_all_price->price_profit_lost_str;
        $price_general = (float) $profit_lost_all_price->price_general;
        $price_profit_final = (float) $profit_lost_all_price->price_prift_lost_final_str;

        if ($pure) {
            return [
                'price_po_excl_ppn' => $price_po_excl_ppn,
                'price_material' => $material_data,
                'price_subkon' => $subkon_data,
                'price_btkl' => $btkl_data,
                'price_other' => $price_other_data,
                'price_profit_lost_project' => $price_profit_lost,
                'price_total' => $price_total,
                'price_profit_lost_po' => $price_profit_lost_po,
                'price_general' => $price_general,
                'price_profit_final' => $price_profit_final
            ];
        }

        return [
            'price_po_excl_ppn' => CustomHelper::formatRupiah($price_po_excl_ppn),
            'price_material' => CustomHelper::formatRupiah($material_data),
            'price_subkon' => CustomHelper::formatRupiah($subkon_data),
            'price_btkl' => CustomHelper::formatRupiah($btkl_data),
            'price_other' => CustomHelper::formatRupiah($price_other_data),
            'price_profit_lost_project' => CustomHelper::formatRupiah($price_profit_lost),
            'price_total' => CustomHelper::formatRupiah($price_total),
            'price_profit_lost_po' => CustomHelper::formatRupiah($price_profit_lost_po),
            'price_general' => CustomHelper::formatRupiah($price_general),
            'price_profit_final' => CustomHelper::formatRupiah($price_profit_final)
        ];
    }

    public function getSelect2Accounts(?string $search): array
    {
        $dataset = Account::select(['id', 'code', 'name'])
            ->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                    ->orWhere('code', 'LIKE', "%$search%");
            })
            ->orderBy('code', 'ASC')
            ->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->code . ' - ' . $item->name,
            ];
        }
        return $results;
    }

    public function getSelect2Po(?string $search): array
    {
        $query = ClientPo::where('work_code', 'like', "%$search%");

        if (request()->has('company_id')) {
            $query->where('company_id', request()->input('company_id'));
        }

        $union = $query->get();

        $results = [];
        foreach ($union as $item) {
            $results[] = [
                'data' => $item,
                'voucher_id' => null,
                'id' => $item->id,
                'po_number' => $item->po_number,
                'text' => $item->work_code,
                'work_code' => $item->work_code,
            ];
        }
        return $results;
    }

    public function getClientPoSelectedData(int $id): array
    {
        $voucher = Voucher::select(
            'client_po_id',
            DB::raw("SUM(payment_transfer) as payment_transfer"),
            DB::raw("SUM(total) as biaya")
        )->where('client_po_id', $id)
            ->groupBy('client_po_id')->first();

        $invoiceClients = DB::table('invoice_clients')
            ->select(
                'client_po_id',
                DB::raw("SUM(price_total_exclude_ppn) AS price_job_exlude_ppn")
            )
            ->groupBy('client_po_id');

        $client_po_query_exclude_ppn = DB::table("client_po")
            ->leftJoinSub($invoiceClients, 'invoice', function ($join) {
                $join->on('invoice.client_po_id', '=', 'client_po.id');
            })->select(
                "client_po.id as client_po_id",
                DB::raw("IF(invoice.price_job_exlude_ppn IS NULL, client_po.job_value, invoice.price_job_exlude_ppn) as price_job_exlude_ppn_logic")
            )->where('client_po.id', $id)->first();

        return [
            'price_voucher' => (int) ($voucher->biaya ?? 0),
            'price_excl_ppn_po' => (int) ($client_po_query_exclude_ppn->price_job_exlude_ppn_logic ?? 0),
        ];
    }

    public function applyListQuery($query, ProfitLostFilterData $filter)
    {
        $voucher = DB::table('vouchers')->select(
            'client_po_id',
            DB::raw("SUM(payment_transfer) as payment_transfer"),
            DB::raw("SUM(total) as biaya")
        )
            ->groupBy('client_po_id');

        $invoice = DB::table('invoice_clients')
            ->select(
                'invoice_clients.client_po_id',
                DB::raw("GROUP_CONCAT(invoice_clients.invoice_date SEPARATOR ',') AS invoice_date"),
                DB::raw("SUM(invoice_clients.price_total_exclude_ppn) as price_job_exlude_ppn"),
                DB::raw("SUM(invoice_clients.price_total_include_ppn) as price_job_include_ppn")
            )
            ->when($filter->year && $filter->year != 'all', function ($query) use ($filter) {
                return $query->whereYear('invoice_date', $filter->year);
            })
            ->groupBy('invoice_clients.client_po_id');

        $client_po_query_exclude_ppn = DB::table("client_po")
            ->leftJoinSub($invoice, 'invoice', function ($join) {
                $join->on('invoice.client_po_id', '=', 'client_po.id');
            })->select(
                "client_po.id as client_po_id",
                "client_po.work_code",
                "client_po.po_number",
                "client_po.reimburse_type",
                "client_po.job_name",
                "client_po.job_value",
                "client_po.category",
                "client_po.job_value_include_ppn",
                "invoice.invoice_date",
                "invoice.price_job_exlude_ppn as invoice_price_job_exlude_ppn",
                "invoice.price_job_include_ppn as invoice_price_job_include_ppn",
                "client_po.date_po",
                DB::raw("IF(invoice.invoice_date IS NULL, client_po.job_value, invoice.price_job_exlude_ppn) as price_job_exlude_ppn_logic"),
                DB::raw("IF(invoice.invoice_date IS NULL, 0, invoice.price_job_include_ppn) as job_value_include_ppn_logic")
            );

        $query->leftJoinSub($client_po_query_exclude_ppn, 'client_po', function ($join) {
            $join->on('client_po.client_po_id', '=', 'project_profit_lost.client_po_id');
        });

        $query->leftJoinSub($voucher, 'vouchers', function ($join) {
            $join->on('vouchers.client_po_id', '=', 'project_profit_lost.client_po_id');
        });

        if ($filter->category) {
            $query->where('client_po.category', $filter->category);
        }

        $query->whereExists(function ($q) use ($filter) {
            $q->select(DB::raw(1))
                ->from('invoice_clients')
                ->whereColumn('invoice_clients.client_po_id', 'project_profit_lost.client_po_id');
            if ($filter->year && $filter->year != 'all') {
                $q->whereYear('invoice_date', $filter->year);
            }
        });

        $query->select([
            DB::raw("
                project_profit_lost.*,
                vouchers.payment_transfer as payment_voucher,
                vouchers.biaya as voucher_biaya,
                client_po.invoice_date,
                client_po.price_job_exlude_ppn_logic,
                client_po.invoice_price_job_include_ppn,
                client_po.work_code as work_code,
                client_po.po_number as po_number,
                client_po.reimburse_type as reimburse_type,
                client_po.job_name as job_name,
                client_po.job_value as job_value,
                client_po.job_value_include_ppn_logic,
                IFNULL(project_profit_lost.price_small_cash, 0) as total_small_cash,
                (IFNULL(project_profit_lost.price_after_year, 0) + IFNULL(vouchers.biaya, 0) + IFNULL(project_profit_lost.price_small_cash, 0)) as price_total_str,
                (client_po.price_job_exlude_ppn_logic - (IFNULL(project_profit_lost.price_after_year, 0) + IFNULL(vouchers.biaya, 0) + IFNULL(project_profit_lost.price_small_cash, 0))) as price_profit_lost_str,
                ((client_po.price_job_exlude_ppn_logic - (IFNULL(project_profit_lost.price_after_year, 0) + IFNULL(vouchers.biaya, 0) + IFNULL(project_profit_lost.price_small_cash, 0))) - IFNULL(project_profit_lost.price_general, 0)) as price_prift_lost_final_str
           ")
        ]);
    }
}
