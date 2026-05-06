<?php

namespace App\Repositories\Fa;

use App\Models\Voucher;
use App\DTOs\Fa\VoucherFilterData;
use App\Http\Helpers\CustomHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use NumberToWords\NumberToWords;

class VoucherRepository
{
    public function getFilteredDataQuery(VoucherFilterData $filters)
    {
        $user_id = backpack_user()->id;
        $user_approval = \App\Models\User::permission(['APPROVE VOUCHER', 'APPROVE EDIT VOUCHER'])
            ->where('id', $user_id)
            ->get();

        $v_e = DB::table('voucher_edit')
            ->select(DB::raw('MAX(id) as id'), 'voucher_id')
            ->groupBy('voucher_id');

        $query = Voucher::query()
            ->leftJoin('accounts', 'accounts.id', '=', 'vouchers.account_id')
            ->leftJoinSub($v_e, 'v_e', function ($join) {
                $join->on('v_e.voucher_id', '=', 'vouchers.id');
            })
            ->leftJoin('voucher_edit', 'voucher_edit.id', '=', 'v_e.id');

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        $query->leftJoinSub($a_p, 'a_p', function ($join) {
            $join->on('a_p.model_id', '=', 'voucher_edit.id')
                ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\VoucherEdit"'));
        })
            ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id')
            ->leftJoin('cast_accounts', 'cast_accounts.id', 'vouchers.account_source_id')
            ->leftJoin('log_payments as log_void', function ($join) {
                $join->on('log_void.reference_id', '=', 'vouchers.id')
                    ->where('log_void.reference_type', '=', 'App\\Models\\Voucher')
                    ->where('log_void.name', '=', 'CREATE_PAYMENT_VOUCHER');
            })
            ->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id');

        if ($user_approval->count() > 0) {
            $query->leftJoin('approvals as user_live_approvals', function ($join) use ($user_id) {
                $join->on('user_live_approvals.model_id', '=', 'voucher_edit.id')
                    ->where('user_live_approvals.model_type', 'App\\Models\\VoucherEdit')
                    ->where('user_live_approvals.user_id', $user_id);
            });
            $query->select([
                DB::raw("
                        vouchers.*,
                        (vouchers.bill_value * vouchers.tax_ppn / 100) as total_price_ppn,
                        accounts.name as account_name,
                        accounts.code as account_code,
                        voucher_edit.id as voucer_edit_id,
                        approvals.status as approval_status,
                        approvals.user_id as approval_user_id,
                        approvals.no_apprv as approval_no_apprv,
                        cast_accounts.name as cast_account_name,
                        log_void.id as payment_log_id,
                        user_live_approvals.no_apprv as user_live_no_apprv,
                        user_live_approvals.status as user_live_status,
                        user_live_approvals.user_id as user_live_user_id,
                        companies.name as company_name
                ")
            ]);
        } else {
            $query->select([
                DB::raw("
                        vouchers.*,
                        (vouchers.bill_value * vouchers.tax_ppn / 100) as total_price_ppn,
                        accounts.name as account_name,
                        accounts.code as account_code,
                        voucher_edit.id as voucer_edit_id,
                        approvals.status as approval_status,
                        approvals.user_id as approval_user_id,
                        approvals.no_apprv as approval_no_apprv,
                        cast_accounts.name as cast_account_name,
                        log_void.id as payment_log_id,
                        '' as user_live_no_apprv,
                        '' as user_live_status,
                        '' as user_live_user_id,
                        companies.name as company_name
                ")
            ]);
        }

        if ($filters->date_voucher !== null && $filters->date_voucher !== '') {
            $query->where('vouchers.date_voucher', $filters->date_voucher);
        }

        if ($filters->bill_date !== null && $filters->bill_date !== '') {
            $query->where('vouchers.bill_date', $filters->bill_date);
        }

        if ($filters->payment_date !== null && $filters->payment_date !== '') {
            $query->where('vouchers.payment_date', $filters->payment_date);
        }

        if ($filters->year && $filters->year != 'all') {
            $query->whereYear('vouchers.date_voucher', $filters->year);
        }

        return $this->applySearchFilters($query, $filters);
    }

    public function applySearchFilters($query, VoucherFilterData $filters)
    {
        if (empty($filters->columnFilters)) return $query;

        $filterMap = [
            2 => ['field' => 'companies.name', 'type' => 'like'],
            3 => ['field' => 'no_voucher', 'type' => 'like'],
            5 => ['field' => 'subkon.name', 'type' => 'relation', 'relation' => 'subkon'],
            6 => ['field' => 'bill_number', 'type' => 'like_start'],
            8 => ['field' => 'payment_description', 'type' => 'like'],
            9 => ['field' => 'bill_value', 'type' => 'like'],
            10 => ['field' => 'total_price_ppn', 'type' => 'raw', 'raw' => '(vouchers.bill_value * vouchers.tax_ppn / 100) like ?'],
            11 => ['field' => 'total', 'type' => 'like'],
            12 => ['field' => 'payment_transfer', 'type' => 'like'],
            13 => ['field' => 'no_factur', 'type' => 'like'],
            14 => ['field' => 'factur_status', 'type' => 'like_start'],
            15 => ['field' => 'client_po.work_code', 'type' => 'relation', 'relation' => 'client_po', 'relation_field' => 'work_code'],
            16 => ['field' => 'job_name', 'type' => 'like'],
            17 => ['field' => 'accounts', 'type' => 'custom', 'callback' => function($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('accounts.code', 'LIKE', '%' . $search . '%')
                        ->orWhere('accounts.name', 'LIKE', "%" . $search . "%");
                });
            }],
            18 => ['field' => 'cast_accounts.name', 'type' => 'like'],
            19 => ['field' => 'payment_type', 'type' => 'like'],
            20 => ['field' => 'approvals.status', 'type' => 'like'],
            20 => ['field' => 'user_approval', 'type' => 'custom', 'callback' => function ($q, $search) {
                $q->whereExists(function ($query) use ($search) {
                    $query->select(DB::raw(1))
                        ->from('approvals')
                        ->whereColumn('approvals.model_id', 'voucher_edit.id')
                        ->where('approvals.model_type', 'App\\Models\\VoucherEdit')
                        ->whereExists(function ($q2) use ($search) {
                            $q2->select(DB::raw(1))
                                ->from('users')
                                ->whereColumn('users.id', 'approvals.user_id')
                                ->where('users.name', 'like', '%' . $search . '%');
                        });
                });
            }],
            21 => ['field' => 'payment_status', 'type' => 'like_start'],
        ];

        foreach ($filterMap as $index => $config) {
            $searchValue = $filters->getColumnFilter($index);
            if ($searchValue === null || $searchValue === '') continue;

            switch ($config['type']) {
                case 'like':
                    $query->where($config['field'], 'like', "%{$searchValue}%");
                    break;
                case 'like_start':
                    $query->where($config['field'], 'like', "{$searchValue}%");
                    break;
                case 'raw':
                    $query->whereRaw($config['raw'], ['%' . $searchValue . '%']);
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

    public function getSummaryValues(VoucherFilterData $filters)
    {
        $data = Voucher::selectRaw('
            SUM(bill_value) as jumlah_exclude_ppn,
            SUM(total) as jumlah_include_ppn,
            SUM(payment_transfer) as jumlah_nilai_transfer
        ');

        $v_e = DB::table('voucher_edit')
            ->select(DB::raw('MAX(id) as id'), 'voucher_id')
            ->groupBy('voucher_id');

        $data->leftJoin('accounts', 'accounts.id', '=', 'vouchers.account_id')
            ->leftJoinSub($v_e, 'v_e', function ($join) {
                $join->on('v_e.voucher_id', '=', 'vouchers.id');
            })
            ->leftJoin('voucher_edit', 'voucher_edit.id', '=', 'v_e.id');

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        $data->leftJoinSub($a_p, 'a_p', function ($join) {
            $join->on('a_p.model_id', '=', 'voucher_edit.id')
                ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\VoucherEdit"'));
        })
            ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id');

        $data->leftJoin('cast_accounts', 'cast_accounts.id', 'vouchers.account_source_id');
        $data->leftJoin('companies', 'companies.id', '=', 'vouchers.company_id');

        if ($filters->date_voucher !== null && $filters->date_voucher !== '') {
            $data->where('vouchers.date_voucher', $filters->date_voucher);
        }

        if ($filters->bill_date !== null && $filters->bill_date !== '') {
            $data->where('vouchers.bill_date', $filters->bill_date);
        }

        if ($filters->payment_date !== null && $filters->payment_date !== '') {
            $data->where('vouchers.payment_date', $filters->payment_date);
        }

        if ($filters->year && $filters->year != 'all') {
            $data->whereYear('vouchers.date_voucher', $filters->year);
        }

        $data = $this->applySearchFilters($data, $filters);

        $result = $data->first();

        return [
            'total_exclude_ppn' => $result->jumlah_exclude_ppn ?? 0,
            'total_include_ppn' => $result->jumlah_include_ppn ?? 0,
            'total_nilai_transfer' => $result->jumlah_nilai_transfer ?? 0,
        ];
    }

    public function generateNextVoucherNumber()
    {
        $total_voucher = Voucher::select('no_voucher')->orderBy('id', 'desc')->first();
        if (!$total_voucher) {
            $numAdd = 1;
            return $numAdd;
        }
        $numAdd = explode('-', $total_voucher->no_voucher)[0];
        $iterasi = 20;
        $countI = 0;
        do {
            $countI++;
            $numAdd++;

            $checkVoucherExists = Voucher::where('no_voucher', 'LIKE', $numAdd . '%')->first();

            if ($countI >= $iterasi) {
                break;
            }
        } while ($checkVoucherExists);

        return $numAdd;
    }

    public function findForPrint(int $id)
    {
        Carbon::setLocale('id');
        $voucher = Voucher::findOrFail($id);
        $voucher->total_str = CustomHelper::formatRupiahWithCurrency($voucher->total);
        $voucher->discount_pph_23_str = CustomHelper::formatRupiahWithCurrency($voucher->discount_pph_23);
        $voucher->discount_pph_4_str = CustomHelper::formatRupiahWithCurrency($voucher->discount_pph_4);
        $voucher->bill_value_str = CustomHelper::formatRupiahWithCurrency($voucher->bill_value);
        $voucher->discount_pph_21_str = CustomHelper::formatRupiahWithCurrency($voucher->discount_pph_21);
        $voucher->payment_transfer_str = CustomHelper::formatRupiahWithCurrency($voucher->payment_transfer);

        $price_ppn = ($voucher->bill_value * ($voucher->tax_ppn / 100));
        $voucher->price_ppn_str = CustomHelper::formatRupiahWithCurrency($price_ppn);

        if ($voucher->reference_type == \App\Models\PurchaseOrder::class) {
            $voucher->reference_date_str = $voucher->reference ? Carbon::parse($voucher->reference->date_po)->translatedFormat('d F Y') : '';
        } else {
            $voucher->reference_date_str = ($voucher->reference && $voucher->reference->date_spk) ? Carbon::parse($voucher->reference->date_spk)->translatedFormat('d F Y') : '';
        }

        $voucher->date_receipt_bill_str = Carbon::parse($voucher->date_receipt_bill)->translatedFormat('d F Y');
        $voucher->date_voucher_str = Carbon::parse($voucher->date_voucher)->translatedFormat('d F Y');
        $voucher->due_date_str = Carbon::parse($voucher->due_date)->translatedFormat('d F Y');
        $voucher->bill_date_str = Carbon::parse($voucher->bill_date)->translatedFormat('d F Y');

        $voucher->date_factur_str = $voucher->date_factur ? Carbon::parse($voucher->date_factur)->translatedFormat('d F Y') : '';
        $voucher->payment_date_str = $voucher->payment_date ? Carbon::parse($voucher->payment_date)->translatedFormat('d F Y') : '';

        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('id');
        $voucher->payment_transfer_word = ucwords($numberTransformer->toWords($voucher->payment_transfer));

        $voucher->date_now_str = Carbon::now()->translatedFormat('d F Y');

        return $voucher;
    }

    public function findByIdWithApprovalStatus(int $id)
    {
        $v_e = DB::table('voucher_edit')
            ->select(DB::raw('MAX(id) as id'), 'voucher_id')
            ->groupBy('voucher_id');

        $a_p = DB::table('approvals')
            ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
            ->groupBy('model_type', 'model_id');

        return Voucher::query()
            ->leftJoinSub($v_e, 'v_e', function ($join) {
                $join->on('v_e.voucher_id', '=', 'vouchers.id');
            })
            ->leftJoin('voucher_edit', 'voucher_edit.id', '=', 'v_e.id')
            ->leftJoinSub($a_p, 'a_p', function ($join) {
                $join->on('a_p.model_id', '=', 'voucher_edit.id')
                    ->where('a_p.model_type', '=', 'App\\Models\\VoucherEdit');
            })
            ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id')
            ->where('vouchers.id', $id)
            ->select(DB::raw("
                vouchers.*,
                voucher_edit.id as voucer_edit_id,
                approvals.status as approval_status,
                approvals.user_id as approval_user_id,
                approvals.no_apprv as approval_no_apprv
            "))
            ->first();
    }
}
