<?php

namespace App\Repositories\Fa;

use Illuminate\Support\Facades\DB;
use App\Models\PaymentVoucher;
use App\Models\Voucher;
use App\Models\Approval;
use App\DTOs\Fa\VoucherPaymentFilterData;
use Carbon\Carbon;
use App\Http\Helpers\CustomHelper;
use App\Models\Setting;

class VoucherPaymentRepository
{
    public function getTotalVoucher(VoucherPaymentFilterData $dto): array
    {
        $non_rutin_filter = $dto->non_rutin;
        $rutin_filter = $dto->rutin;

        $total_voucher_data_non_rutin = PaymentVoucher::select(DB::raw('SUM(vouchers.payment_transfer) as jumlah_nilai_transfer'));

        $p_v_p = DB::table('payment_voucher_plan')
            ->select(DB::raw('MAX(id) as id'), 'payment_voucher_id')
            ->groupBy('payment_voucher_id');

        $total_voucher_data_non_rutin = $total_voucher_data_non_rutin
            ->leftJoin('vouchers', 'vouchers.id', '=', 'payment_vouchers.voucher_id')
            ->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
            ->leftJoinSub($p_v_p, 'p_v_p', function ($join) {
                $join->on('p_v_p.payment_voucher_id', '=', 'payment_vouchers.id');
            })
            ->leftJoin('payment_voucher_plan', 'payment_voucher_plan.id', '=', 'p_v_p.id');

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        $total_voucher_data_non_rutin = $total_voucher_data_non_rutin
            ->leftJoinSub($a_p, 'a_p', function ($join) {
                $join->on('a_p.model_id', '=', 'payment_voucher_plan.id')
                    ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\PaymentVoucherPlan"'));
            })
            ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id');

        $total_voucher_data_non_rutin = $total_voucher_data_non_rutin
            ->leftJoin('spk', function ($join) {
                $join->on('spk.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\Spk"'));
            })
            ->leftJoin('subkons','subkons.id','=','vouchers.subkon_id')
            ->leftJoin('purchase_orders', function ($join) {
                $join->on('purchase_orders.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\PurchaseOrder"'));
            })
            ->where('payment_vouchers.payment_type', 'NON RUTIN')
            ->where('vouchers.payment_status', 'BAYAR')
            ->groupBy('payment_vouchers.payment_type');

        if ($non_rutin_filter) {
            $decoded = is_string($non_rutin_filter) ? json_decode($non_rutin_filter, true) : $non_rutin_filter;
            $non_rutin_columns = $decoded['columns'] ?? [];
            $total_voucher_data_non_rutin = $this->applyFilters($total_voucher_data_non_rutin, $non_rutin_columns);
        }

        if ($dto->filter_year && $dto->filter_year != 'all') {
            $total_voucher_data_non_rutin = $total_voucher_data_non_rutin->whereYear('vouchers.date_voucher', $dto->filter_year);
        }

        $total_voucher_data_non_rutin = $total_voucher_data_non_rutin->first();

        // RUTIN
        $total_voucher_data_rutin = PaymentVoucher::select(DB::raw('SUM(vouchers.payment_transfer) as jumlah_nilai_transfer'));

        $total_voucher_data_rutin = $total_voucher_data_rutin
            ->leftJoin('vouchers', 'vouchers.id', '=', 'payment_vouchers.voucher_id')
            ->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
            ->leftJoinSub($p_v_p, 'p_v_p', function ($join) {
                $join->on('p_v_p.payment_voucher_id', '=', 'payment_vouchers.id');
            })
            ->leftJoin('payment_voucher_plan', 'payment_voucher_plan.id', '=', 'p_v_p.id');

        $total_voucher_data_rutin = $total_voucher_data_rutin
            ->leftJoinSub($a_p, 'a_p', function ($join) {
                $join->on('a_p.model_id', '=', 'payment_voucher_plan.id')
                    ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\PaymentVoucherPlan"'));
            })
            ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id');

        $total_voucher_data_rutin = $total_voucher_data_rutin
            ->leftJoin('spk', function ($join) {
                $join->on('spk.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\Spk"'));
            })
            ->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
            ->leftJoin('purchase_orders', function ($join) {
                $join->on('purchase_orders.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\PurchaseOrder"'));
            })
            ->where('payment_vouchers.payment_type', 'SUBKON')
            ->where('vouchers.payment_status', 'BAYAR')
            ->groupBy('payment_vouchers.payment_type');

        if ($rutin_filter) {
            $decoded = is_string($rutin_filter) ? json_decode($rutin_filter, true) : $rutin_filter;
            $rutin_columns = $decoded['columns'] ?? [];
            $total_voucher_data_rutin = $this->applyFilters($total_voucher_data_rutin, $rutin_columns);
        }

        if ($dto->filter_year && $dto->filter_year != 'all') {
            $total_voucher_data_rutin = $total_voucher_data_rutin->whereYear('vouchers.date_voucher', $dto->filter_year);
        }

        $total_voucher_data_rutin = $total_voucher_data_rutin->first();

        return [
            'voucher_payment_non_rutin_total' => CustomHelper::formatRupiahWithCurrency(($total_voucher_data_non_rutin != null) ? $total_voucher_data_non_rutin->jumlah_nilai_transfer : 0),
            'voucher_payment_rutin_total' => CustomHelper::formatRupiahWithCurrency(($total_voucher_data_rutin != null) ? $total_voucher_data_rutin->jumlah_nilai_transfer : 0),
        ];
    }

    public function applyListQuery($query, VoucherPaymentFilterData $dto)
    {
        $user_id = backpack_user()->id;
        $user_approval = \App\Models\User::permission(['APPROVE VOUCHER', 'APPROVE EDIT VOUCHER'])
            ->where('id', $user_id)
            ->get();

        $query->leftJoin('vouchers', 'vouchers.id', '=', 'payment_vouchers.voucher_id')
            ->leftJoin('log_payments as log_void', function ($join) {
                $join->on('log_void.reference_id', '=', 'vouchers.id')
                    ->where('log_void.reference_type', '=', DB::raw('"App\\\\Models\\\\Voucher"'))
                    ->where('log_void.name', '=', DB::raw('"CREATE_PAYMENT_VOUCHER"'));
            })
            ->leftJoin('approvals', function ($join) {
                $join->on('approvals.model_id', '=', 'vouchers.id')
                    ->where('approvals.model_type', '=', DB::raw('"App\\\\Models\\\\Voucher"'));
            })
            ->leftJoin('subkons','subkons.id','=','vouchers.subkon_id')
            ->leftJoin('spk', function ($join) {
                $join->on('spk.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\Spk"'));
            })
            ->leftJoin('purchase_orders', function ($join) {
                $join->on('purchase_orders.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\PurchaseOrder"'));
            })
            ->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id');

        if ($user_approval->count() > 0) {
            $query->leftJoin('approvals as user_live_approvals', function ($join) use ($user_id) {
                $join->on('user_live_approvals.model_id', '=', 'vouchers.id')
                    ->where('user_live_approvals.model_type', 'App\\Models\\Voucher')
                    ->where('user_live_approvals.user_id', $user_id);
            });

            $query->select([
                DB::raw("
                    vouchers.*,
                    companies.name as company_name,
                    spk.no_spk as spk_no,
                    purchase_orders.po_number as po_no,
                    approvals.approved_at as approval_approved_at,
                    approvals.status as approval_status,
                    approvals.user_id as approval_user_id,
                    approvals.no_apprv as approval_no_apprv,
                    payment_vouchers.voucher_id as voucer_edit_id,
                    log_void.id as payment_log_id,
                    user_live_approvals.no_apprv as user_live_no_apprv,
                    user_live_approvals.status as user_live_status,
                    user_live_approvals.user_id as user_live_user_id,
                    subkons.name as subkon_name
                ")
            ]);
        } else {
            $query->select([
                DB::raw("
                    vouchers.*,
                    companies.name as company_name,
                    spk.no_spk as spk_no,
                    purchase_orders.po_number as po_no,
                    approvals.approved_at as approval_approved_at,
                    approvals.status as approval_status,
                    approvals.user_id as approval_user_id,
                    approvals.no_apprv as approval_no_apprv,
                    payment_vouchers.voucher_id as voucher_edit_id,
                    log_void.id as payment_log_id,
                    '' as user_live_no_apprv,
                    '' as user_live_status,
                    '' as user_live_user_id,
                    subkons.name as subkon_name
                ")
            ]);
        }

        if ($dto->type == 'export') {
            $objCopy = $this;
            $query->where(function ($query) use ($dto, $objCopy) {
                $query->where(function ($query) use ($dto, $objCopy) {
                    $query->where('payment_vouchers.payment_type', 'NON RUTIN')
                        ->where(function ($query) use ($dto, $objCopy) {
                            $objCopy->applyFilters($query, $dto->searchNonRutin['columns']);
                        });
                })
                    ->orWhere(function ($query) use ($dto, $objCopy) {
                        $query->where('payment_vouchers.payment_type', 'SUBKON')
                            ->where(function ($query) use ($dto, $objCopy) {
                                $objCopy->applyFilters($query, $dto->searchRutin['columns']);
                            });
                    });
            });

            if ($dto->filter_year && $dto->filter_year != 'all') {
                $query->whereYear('vouchers.date_voucher', $dto->filter_year);
            }
        } else {
            if ($dto->type == 'NON RUTIN') {
                $query->where('payment_vouchers.payment_type', 'NON RUTIN');
            } else {
                $query->where('payment_vouchers.payment_type', 'SUBKON');
            }

            if ($dto->filter_year && $dto->filter_year != 'all') {
                $query->whereYear('vouchers.date_voucher', $dto->filter_year);
            }

            // Apply column filters
            $this->applyFilters($query, $dto->columns ?? []);
        }

        return $query;
    }

    private function applyFilters($query, array $columns)
    {
        $isSuperAdmin = backpack_user()->hasRole('Super Admin');
        $offset = $isSuperAdmin ? 1 : 0;

        $filterMap = [
            (2 + $offset)  => ['field' => 'no_voucher', 'type' => 'like'],
            (3 + $offset)  => ['field' => 'date_voucher', 'type' => 'like'],
            (4 + $offset)  => ['field' => 'subkons.name', 'type' => 'like'],
            (5 + $offset)  => ['field' => 'bill_date', 'type' => 'like'],
            (6 + $offset)  => ['field' => 'reference', 'type' => 'custom', 'callback' => function ($q, $search) {
                $q->whereHas('voucher', function ($q2) use ($search) {
                    $q2->whereHasMorph('reference', '*', function ($q3) use ($search) {
                        $q3->where('po_number', 'like', "%{$search}%")
                            ->orWhere('no_spk', 'like', "%{$search}%");
                    });
                });
            }],
            (7 + $offset)  => ['field' => 'payment_transfer', 'type' => 'like'],
            (8 + $offset)  => ['field' => 'vouchers.due_date', 'type' => 'like'],
            (9 + $offset)  => ['field' => 'factur_status', 'type' => 'like_start'],
            (10 + $offset) => ['field' => 'payment_date', 'type' => 'like'],
            (11 + $offset) => ['field' => 'payment_status', 'type' => 'like_start'],
            (12 + $offset) => ['field' => 'approvals.approved_at', 'type' => 'like'],
            (13 + $offset) => ['field' => 'approvals.status', 'type' => 'like'],
            (14 + $offset) => ['field' => 'user_approval', 'type' => 'custom', 'callback' => function ($q, $search) {
                $q->whereExists(function ($query) use ($search) {
                    $query->select(DB::raw(1))
                        ->from('approvals')
                        ->whereColumn('approvals.model_id', 'vouchers.id')
                        ->where('approvals.model_type', 'App\\Models\\Voucher')
                        ->whereExists(function ($q2) use ($search) {
                            $q2->select(DB::raw(1))
                                ->from('users')
                                ->whereColumn('users.id', 'approvals.user_id')
                                ->where('users.name', 'like', '%' . $search . '%');
                        });
                });
            }],
        ];

        if ($isSuperAdmin) {
            $filterMap[2] = ['field' => 'companies.name', 'type' => 'like'];
        }

        foreach ($filterMap as $index => $config) {
            $searchValue = trim($columns[$index]['search']['value'] ?? '');
            if ($searchValue === '') continue;

            switch ($config['type']) {
                case 'like':
                    $query->where($config['field'], 'like', "%{$searchValue}%");
                    break;
                case 'like_start':
                    $query->where($config['field'], 'like', "{$searchValue}%");
                    break;
                case 'relation':
                    $relation = $config['relation'];
                    $field = $config['relation_field'] ?? str_replace($relation . '.', '', $config['field']);
                    $query->whereHas($relation, function ($q) use ($field, $searchValue) {
                        $q->where($field, 'like', "%{$searchValue}%");
                    });
                    break;
                case 'custom':
                    $config['callback']($query, $searchValue);
                    break;
            }
        }

        return $query;
    }

    public function getDatatableVoucher(VoucherPaymentFilterData $dto): array
    {
        $settings = Setting::first();

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        $query = Voucher::leftJoin('accounts', 'accounts.id', '=', 'vouchers.account_id')
            ->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
            ->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id')
            ->leftJoinSub($a_p, 'a_p', function ($join) {
                $join->on('a_p.model_id', '=', 'vouchers.id')
                    ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\Voucher"'));
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
            ->where(function ($query) {
                $query->where('approvals.status', Approval::APPROVED)
                    ->orWhereNull('approvals.status');
            })
            ->where('vouchers.payment_status', 'BELUM BAYAR')
            ->select(DB::raw("
                vouchers.*,
                subkons.name as subkon_name,
                companies.name as company_name,
                spk.no_spk as spk_no,
                purchase_orders.po_number as po_no
            "));

        $totalData = (clone $query)->count('vouchers.id');

        // Searching
        if ($dto->search && isset($dto->search['value']) && $dto->search['value']) {
            $search = $dto->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('vouchers.no_voucher', 'like', "%{$search}%")
                    ->orWhere('vouchers.account_holder_name', 'like', "%{$search}%")
                    ->orWhere('subkons.name', 'like', "%{$search}%")
                    ->orWhere('spk.no_spk', 'like', "%{$search}%")
                    ->orWhere('vouchers.payment_description', 'like', "%{$search}%")
                    ->orWhere('purchase_orders.po_number', 'like', "%{$search}%");
                if (backpack_user()->hasRole('Super Admin')) {
                    $q->orWhere('companies.name', 'like', "%{$search}%");
                }
            });
        }

        $totalFiltered = (clone $query)->count('vouchers.id');

        // Ordering
        $order = $dto->order;
        if (isset($order[0]['column'])) {
            $columnIndex = $order[0]['column'];
            $columnDir = $order[0]['dir'];
            $columns = $dto->columns;
            $columnData = $columns[$columnIndex]['data'] ?? '';
            $columnName = $columns[$columnIndex]['name'] ?? '';

            if ($columnName == 'date_voucher' || $columnData == 'date_voucher') {
                $query->orderBy('vouchers.date_voucher', $columnDir);
            } elseif ($columnName == 'payment_type' || $columnData == 'payment_type') {
                $query->orderBy('vouchers.payment_type', $columnDir);
            } else {
                $query->orderBy('vouchers.date_voucher', 'desc');
            }
        } else {
            $query->orderBy('vouchers.date_voucher', 'desc');
        }

        $vouchers = $query->offset($dto->start)
            ->limit($dto->length)
            ->get();

        $data = [];
        foreach ($vouchers as $v) {
            $data[] = [
                'id' => $v->id,
                'no_voucher' => $v->no_voucher,
                'date_voucher' => Carbon::parse($v->date_voucher)->format('d/m/Y'),
                'subkon_name' => $v->subkon_name,
                'company_name' => $v->company_name,
                'bill_date' => Carbon::parse($v->bill_date)->format('d/m/Y'),
                'reference_no' => ($v->reference_type == 'App\Models\Spk') ? $v->spk_no : $v->po_no,
                'payment_transfer' => ($settings?->currency_symbol) ? $settings->currency_symbol . ' ' . CustomHelper::formatRupiah($v->payment_transfer) : "Rp." . CustomHelper::formatRupiah($v->payment_transfer),
                'due_date' => Carbon::parse($v->due_date)->format('d/m/Y'),
                'factur_status' => $v->factur_status,
                'payment_type' => $v->payment_type,
                'payment_description' => $v->payment_description,
            ];
        }

        return [
            "draw" => $dto->draw,
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
    }
}
