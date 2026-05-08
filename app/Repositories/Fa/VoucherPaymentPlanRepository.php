<?php

namespace App\Repositories\Fa;

use Carbon\Carbon;
use App\Models\Spk;
use App\Models\Voucher;
use App\Models\Approval;
use App\Models\Setting;
use App\Models\PurchaseOrder;
use App\Models\PaymentVoucher;
use App\Http\Helpers\CustomHelper;
use App\DTOs\Fa\VoucherPaymentPlanFilterData;
use Illuminate\Support\Facades\DB;

class VoucherPaymentPlanRepository
{
    // -------------------------------------------------------------------------
    // Total Voucher Aggregates (untuk card summary)
    // -------------------------------------------------------------------------

    public function getTotalVoucher(VoucherPaymentPlanFilterData $dto): array
    {
        $p_v_p = DB::table('payment_voucher_plan')
            ->select(DB::raw('MAX(id) as id'), 'payment_voucher_id')
            ->groupBy('payment_voucher_id');

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        // Base query builder untuk plan yang sudah diapprove
        $basePlanQuery = function (?string $type = null) use ($p_v_p, $a_p) {
            $query = PaymentVoucher::select(DB::raw('SUM(vouchers.payment_transfer) as jumlah_nilai_transfer'))
                ->leftJoin('vouchers', 'vouchers.id', '=', 'payment_vouchers.voucher_id')
                ->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
                ->leftJoinSub($p_v_p, 'p_v_p', function ($join) {
                    $join->on('p_v_p.payment_voucher_id', '=', 'payment_vouchers.id');
                })
                ->leftJoin('payment_voucher_plan', 'payment_voucher_plan.id', '=', 'p_v_p.id')
                ->leftJoinSub($a_p, 'a_p', function ($join) {
                    $join->on('a_p.model_id', '=', 'payment_voucher_plan.id')
                        ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\PaymentVoucherPlan"'));
                })
                ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id')
                ->leftJoin('spk', function ($join) {
                    $join->on('spk.id', '=', 'vouchers.reference_id')
                        ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\Spk"'));
                })
                ->leftJoin('purchase_orders', function ($join) {
                    $join->on('purchase_orders.id', '=', 'vouchers.reference_id')
                        ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\PurchaseOrder"'));
                })
                ->leftJoin('cast_accounts', 'cast_accounts.id', 'vouchers.account_source_id')
                ->where('vouchers.payment_status', 'BELUM BAYAR')
                ->where('approvals.status', Approval::APPROVED);

            if ($type) {
                $query->where('payment_vouchers.payment_type', $type);
            }

            return $query;
        };

        // 0. ALL PLAN
        $queryAll = $basePlanQuery();
        if ($dto->filter_year && $dto->filter_year !== 'all') {
            $queryAll->whereYear('vouchers.date_voucher', $dto->filter_year);
        }
        $queryAll = $this->applyPlanFilters($queryAll, $dto->searchAll['columns']);
        $totalAll = $queryAll->first();

        // 1. NON RUTIN (dari Voucher langsung, belum masuk payment)
        $queryNonRutin = Voucher::leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
            ->where('vouchers.payment_type', 'NON RUTIN')
            ->where('vouchers.payment_status', 'BELUM BAYAR')
            ->select(DB::raw('SUM(vouchers.payment_transfer) as jumlah_nilai_transfer'));
        if ($dto->filter_year && $dto->filter_year !== 'all') {
            $queryNonRutin->whereYear('date_voucher', $dto->filter_year);
        }
        $queryNonRutin = $this->applyVoucherFilters($queryNonRutin, $dto->searchNonRutin['columns']);
        $totalNonRutin = $queryNonRutin->first();

        // 2. SUBKON (dari Voucher langsung, belum masuk payment)
        $querySubkon = Voucher::leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
            ->where('vouchers.payment_type', 'SUBKON')
            ->where('vouchers.payment_status', 'BELUM BAYAR')
            ->select(DB::raw('SUM(vouchers.payment_transfer) as jumlah_nilai_transfer'));
        if ($dto->filter_year && $dto->filter_year !== 'all') {
            $querySubkon->whereYear('date_voucher', $dto->filter_year);
        }
        $querySubkon = $this->applyVoucherFilters($querySubkon, $dto->searchSubkon['columns']);
        $totalSubkon = $querySubkon->first();

        return [
            'voucher_payment_plan_all_total'      => CustomHelper::formatRupiahWithCurrency($totalAll?->jumlah_nilai_transfer ?? 0),
            'voucher_payment_plan_non_rutin_total' => CustomHelper::formatRupiahWithCurrency($totalNonRutin?->jumlah_nilai_transfer ?? 0),
            'voucher_payment_subkon_total'         => CustomHelper::formatRupiahWithCurrency($totalSubkon?->jumlah_nilai_transfer ?? 0),
            'voucher_payment_plan_subkon_total'    => CustomHelper::formatRupiahWithCurrency($totalSubkon?->jumlah_nilai_transfer ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Datatable Voucher (modal pilih voucher saat create)
    // -------------------------------------------------------------------------

    public function getDatatableVoucher(VoucherPaymentPlanFilterData $dto): array
    {
        $settings = Setting::first();

        $v_e = DB::table('voucher_edit')
            ->select(DB::raw('MAX(id) as id'), 'voucher_id')
            ->groupBy('voucher_id');

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        $query = Voucher::leftJoin('accounts', 'accounts.id', '=', 'vouchers.account_id')
            ->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
            ->leftJoinSub($v_e, 'v_e', function ($join) {
                $join->on('v_e.voucher_id', '=', 'vouchers.id');
            })
            ->leftJoin('voucher_edit', 'voucher_edit.id', '=', 'v_e.id')
            ->leftJoinSub($a_p, 'a_p', function ($join) {
                $join->on('a_p.model_id', '=', 'voucher_edit.id')
                    ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\VoucherEdit"'));
            })
            ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id')
            ->leftJoin('spk', function ($join) {
                $join->on('spk.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\Spk"'));
            })
            ->leftJoin('purchase_orders', function ($join) {
                $join->on('purchase_orders.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\PurchaseOrder"'));
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('payment_vouchers')
                    ->whereColumn('payment_vouchers.voucher_id', 'vouchers.id');
            })
            ->where('approvals.status', Approval::APPROVED)
            ->select(DB::raw("
                vouchers.*,
                subkons.name as subkon_name,
                spk.no_spk as spk_no,
                purchase_orders.po_number as po_no
            "));

        $totalData = (clone $query)->count('vouchers.id');

        // Search
        $searchValue = $dto->search['value'] ?? null;
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('vouchers.no_voucher', 'like', "%{$searchValue}%")
                    ->orWhere('vouchers.account_holder_name', 'like', "%{$searchValue}%")
                    ->orWhere('subkons.name', 'like', "%{$searchValue}%")
                    ->orWhere('spk.no_spk', 'like', "%{$searchValue}%")
                    ->orWhere('vouchers.payment_description', 'like', "%{$searchValue}%")
                    ->orWhere('purchase_orders.po_number', 'like', "%{$searchValue}%");
            });
        }

        $totalFiltered = (clone $query)->count('vouchers.id');

        // Ordering
        $order = $dto->order;
        if (isset($order[0]['column'])) {
            $columnIndex = $order[0]['column'];
            $columnDir   = $order[0]['dir'];
            $columns     = $dto->columns;
            $columnData  = $columns[$columnIndex]['data'] ?? '';
            $columnName  = $columns[$columnIndex]['name'] ?? '';

            if ($columnName === 'date_voucher' || $columnData === 'date_voucher') {
                $query->orderBy('vouchers.date_voucher', $columnDir);
            } elseif ($columnName === 'payment_type' || $columnData === 'payment_type') {
                $query->orderBy('vouchers.payment_type', $columnDir);
            } else {
                $query->orderBy('vouchers.date_voucher', 'desc');
            }
        } else {
            $query->orderBy('vouchers.date_voucher', 'desc');
        }

        $vouchers = $query->offset($dto->start)->limit($dto->length)->get();

        $data = [];
        foreach ($vouchers as $v) {
            $data[] = [
                'id'                  => $v->id,
                'no_voucher'          => $v->no_voucher,
                'date_voucher'        => Carbon::parse($v->date_voucher)->format('d/m/Y'),
                'subkon_name'         => $v->account_holder_name ?? $v->reference?->subkon?->account_holder_name,
                'bill_date'           => Carbon::parse($v->bill_date)->format('d/m/Y'),
                'reference_no'        => ($v->reference_type === 'App\Models\Spk') ? $v->spk_no : $v->po_no,
                'payment_transfer'    => ($settings?->currency_symbol)
                    ? $settings->currency_symbol . ' ' . CustomHelper::formatRupiah($v->payment_transfer)
                    : 'Rp.' . CustomHelper::formatRupiah($v->payment_transfer),
                'due_date'            => Carbon::parse($v->due_date)->format('d/m/Y'),
                'factur_status'       => $v->factur_status,
                'payment_type'        => $v->payment_type,
                'payment_description' => $v->payment_description,
            ];
        }

        return [
            'draw'            => $dto->draw,
            'recordsTotal'    => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data'            => $data,
        ];
    }

    // -------------------------------------------------------------------------
    // Private: filter helpers
    // -------------------------------------------------------------------------

    /**
     * Apply column-based filters to the plan (PaymentVoucher-based) query
     * for the total_voucher calculation (all tab).
     */
    private function applyPlanFilters($query, mixed $columns): mixed
    {
        $search = $this->extractFilterValues($columns);
        if (empty($search)) return $query;

        $isSuperAdmin = backpack_user()->hasRole('Super Admin');
        $offset = $isSuperAdmin ? 1 : 0;

        $filters = [
            (2 + $offset)  => fn($q, $v) => $q->whereHas('voucher.subkon', fn($s) => $s->where('bank_name', 'like', "%{$v}%")),
            (3 + $offset)  => fn($q, $v) => $q->whereHas('voucher.subkon', fn($s) => $s->where('bank_account', 'like', "%{$v}%")),
            (4 + $offset)  => fn($q, $v) => $q->where(function ($sub) use ($v) {
                $sub->where('vouchers.account_holder_name', 'like', "%{$v}%")
                    ->orWhereHas('voucher', fn($vq) => $vq->whereHasMorph(
                        'reference',
                        [Spk::class, PurchaseOrder::class],
                        fn($rq) => $rq->whereHas('subkon', fn($sq) => $sq->where('account_holder_name', 'like', "%{$v}%"))
                    ));
            }),
            (5 + $offset)  => fn($q, $v) => $q->where('vouchers.payment_transfer', 'like', "%{$v}%"),
            (6 + $offset)  => fn($q, $v) => $q->where('vouchers.no_voucher', 'like', "%{$v}%"),
            (7 + $offset)  => fn($q, $v) => $q->where('vouchers.bill_number', 'like', "%{$v}%"),
            (8 + $offset)  => fn($q, $v) => $q->where('vouchers.payment_description', 'like', "%{$v}%"),
            (9 + $offset)  => fn($q, $v) => $q->whereHas('voucher', fn($vq) => $vq->whereHasMorph(
                'reference',
                [Spk::class, PurchaseOrder::class],
                function ($rq, $type) use ($v) {
                    if ($type === Spk::class) {
                        $rq->where('no_spk', 'like', "%{$v}%");
                    } else {
                        $rq->where('po_number', 'like', "%{$v}%");
                    }
                }
            )),
            (10 + $offset) => fn($q, $v) => $q->where('vouchers.factur_status', 'like', "{$v}%"),
            (11 + $offset) => fn($q, $v) => $q->where('vouchers.job_name', 'like', "%{$v}%"),
            (12 + $offset) => fn($q, $v) => $q->where('vouchers.due_date', 'like', "%{$v}%"),
            (13 + $offset) => fn($q, $v) => $q->where('vouchers.payment_type', 'like', "%{$v}%"),
        ];

        if ($isSuperAdmin) {
            $filters[2] = fn($q, $v) => $q->where('companies.name', 'like', "%{$v}%");
        }

        foreach ($filters as $index => $apply) {
            $value = trim($search[$index] ?? '');
            if ($value !== '') {
                $query = $apply($query, $value);
            }
        }

        return $query;
    }

    /**
     * Apply column-based filters to Voucher-based queries
     * (non_rutin / subkon tab pada total_voucher).
     */
    public function applyListQuery($query, VoucherPaymentPlanFilterData $dto): void
    {
        $request = request();
        $tab = $dto->tab;

        if ($tab == 'voucher_payment_plan_subkon' || $tab == 'voucher_payment_plan_non_rutin') {
            $payment_type_filter = ($tab == 'voucher_payment_plan_subkon') ? 'SUBKON' : 'NON RUTIN';

            $query->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
                ->select([
                    'vouchers.*',
                    'companies.name as company_name'
                ])
                ->where('vouchers.payment_status', 'BELUM BAYAR')
                ->where('vouchers.payment_type', $payment_type_filter);

            if ($dto->filter_year && $dto->filter_year != 'all') {
                $query->whereYear('vouchers.date_voucher', $dto->filter_year);
            }

            // Tab-specific filters (Voucher based)
            $this->applyVoucherFilters($query, $dto->columns);
            return;
        }

        // Default / All Tab (PaymentVoucher based)
        $user_id = backpack_user()->id;
        $user_approval = \App\Models\User::permission(['APPROVE RENCANA BAYAR'])
            ->where('id', $user_id)
            ->get();

        $p_v_p = DB::table('payment_voucher_plan')
            ->select(DB::raw('MAX(id) as id'), 'payment_voucher_id')
            ->groupBy('payment_voucher_id');

        $query->leftJoin('vouchers', 'vouchers.id', '=', 'payment_vouchers.voucher_id')
            ->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
            ->leftJoinSub($p_v_p, 'p_v_p', function ($join) {
                $join->on('p_v_p.payment_voucher_id', '=', 'payment_vouchers.id');
            })
            ->leftJoin('payment_voucher_plan', 'payment_voucher_plan.id', '=', 'p_v_p.id');

        if ($user_approval->count() > 0) {
            $query->leftJoin('approvals as user_live_approvals', function ($join) use ($user_id) {
                $join->on('user_live_approvals.model_id', '=', 'payment_voucher_plan.id')
                    ->where('user_live_approvals.model_type', 'App\\Models\\PaymentVoucherPlan')
                    ->where('user_live_approvals.user_id', $user_id);
            });

            $query->select(DB::raw(
                "vouchers.*,
                companies.name as company_name,
                spk.no_spk as spk_no,
                purchase_orders.po_number as po_no,
                approvals.approved_at as approval_approved_at,
                approvals.status as approval_status,
                approvals.user_id as approval_user_id,
                approvals.no_apprv as approval_no_apprv,
                payment_voucher_plan.id as voucer_edit_id,
                payment_vouchers.voucher_id,
                user_live_approvals.no_apprv as user_live_no_apprv,
                user_live_status,
                user_live_user_id"
            ));
        } else {
            $query->select(DB::raw(
                "vouchers.*,
                companies.name as company_name,
                spk.no_spk as spk_no,
                purchase_orders.po_number as po_no,
                approvals.approved_at as approval_approved_at,
                approvals.status as approval_status,
                approvals.user_id as approval_user_id,
                approvals.no_apprv as approval_no_apprv,
                payment_voucher_plan.id as voucer_edit_id,
                payment_vouchers.voucher_id,
                '' as user_live_no_apprv,
                '' as user_live_status,
                '' as user_live_user_id"
            ));
        }

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        $query->leftJoinSub($a_p, 'a_p', function ($join) {
            $join->on('a_p.model_id', '=', 'payment_voucher_plan.id')
                ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\PaymentVoucherPlan"'));
        })
            ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id');

        $query->leftJoin('spk', function ($join) {
            $join->on('spk.id', '=', 'vouchers.reference_id')
                ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\Spk"'));
        })
            ->leftJoin('purchase_orders', function ($join) {
                $join->on('purchase_orders.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\PurchaseOrder"'));
        })
            ->leftJoin('cast_accounts', 'cast_accounts.id', 'vouchers.account_source_id')
            ->where('vouchers.payment_status', 'BELUM BAYAR');

        if ($tab == 'voucher_payment_plan_subkon') {
            $query->where('vouchers.payment_type', 'SUBKON');
        } else if ($tab == 'voucher_payment_plan_non_rutin') {
            $query->where('vouchers.payment_type', 'NON RUTIN');
        }

        if ($dto->filter_year && $dto->filter_year != 'all') {
            $query->whereYear('vouchers.date_voucher', $dto->filter_year);
        }

        // Apply column filters for All Tab
        $this->applyPlanFilters($query, $dto->columns);
    }

    private function extractFilterValues(mixed $columns): array
    {
        $values = [];
        if (is_array($columns)) {
            foreach ($columns as $index => $col) {
                $values[$index] = $col['search']['value'] ?? null;
            }
        }
        return $values;
    }

    public function applyVoucherFilters($query, mixed $columns): mixed
    {
        $search = $this->extractFilterValues($columns);
        if (empty($search)) return $query;

        $isSuperAdmin = backpack_user()->hasRole('Super Admin');
        $offset = $isSuperAdmin ? 1 : 0;

        $filters = [
            (1 + $offset) => fn($q, $v) => $q->where('vouchers.no_voucher', 'like', "%{$v}%"),
            (2 + $offset) => fn($q, $v) => $q->whereHas('subkon', fn($s) => $s->where('name', 'like', "%{$v}%")),
            (3 + $offset) => fn($q, $v) => $q->where('vouchers.bill_number', 'like', "%{$v}%"),
            (4 + $offset) => fn($q, $v) => $q->where('vouchers.payment_description', 'like', "%{$v}%"),
            (5 + $offset) => fn($q, $v) => $q->whereHasMorph(
                'reference',
                [Spk::class, PurchaseOrder::class],
                function ($rq, $type) use ($v) {
                    if ($type === Spk::class) {
                        $rq->where('no_spk', 'like', "%{$v}%");
                    } else {
                        $rq->where('po_number', 'like', "%{$v}%");
                    }
                }
            ),
            (6 + $offset) => fn($q, $v) => $q->where('vouchers.total', 'like', "%{$v}%"),
            (7 + $offset) => fn($q, $v) => $q->where('vouchers.job_name', 'like', "%{$v}%"),
            (8 + $offset) => fn($q, $v) => $q->where('vouchers.due_date', 'like', "%{$v}%"),
        ];

        if ($isSuperAdmin) {
            $filters[1] = fn($q, $v) => $q->where('companies.name', 'like', "%{$v}%");
        }

        foreach ($filters as $index => $apply) {
            $value = trim($search[$index] ?? '');
            if ($value !== '') {
                $query = $apply($query, $value);
            }
        }

        return $query;
    }
}
