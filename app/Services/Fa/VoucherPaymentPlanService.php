<?php

namespace App\Services\Fa;

use Carbon\Carbon;
use App\Models\Voucher;
use App\Models\Approval;
use App\Models\PaymentVoucher;
use App\Models\PaymentVoucherPlan;
use App\Http\Helpers\CustomVoid;
use App\Http\Helpers\CustomHelper;
use App\DTOs\Fa\VoucherPaymentPlanStoreData;
use App\DTOs\Fa\VoucherPaymentPlanStoreSingleData;
use App\DTOs\Fa\VoucherPaymentPlanApproveData;
use App\DTOs\Fa\VoucherPaymentPlanBulkApproveData;
use App\DTOs\Fa\VoucherPaymentPlanBulkDeleteData;

class VoucherPaymentPlanService
{
    // -------------------------------------------------------------------------
    // Store: tambahkan rencana pembayaran untuk banyak voucher sekaligus
    // -------------------------------------------------------------------------

    public function store(VoucherPaymentPlanStoreData $dto): array
    {
        $event = [];

        foreach ($dto->voucher as $id_v) {
            $voucher = Voucher::find($id_v);
            if (!$voucher) continue;

            if ($voucher->payment_type === 'SUBKON') {
                $event['crudTable-voucher_payment_plan_subkon_create_success'] = true;
            } else {
                $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
            }
            $event['crudTable-voucher_payment_plan_all_create_success'] = true;

            CustomVoid::voucherPaymentPlan($voucher);
        }

        return $event;
    }

    // -------------------------------------------------------------------------
    // Store Single: tandai satu voucher sebagai sudah bayar dari rencana
    // -------------------------------------------------------------------------

    public function storeSingle(VoucherPaymentPlanStoreSingleData $dto): array
    {
        $voucher = Voucher::findOrFail($dto->id);
        $voucher->payment_status = 'BAYAR';
        $voucher->payment_date   = $dto->date;
        $voucher->save();

        $event = ['crudTable-filter_voucher_payment_plugin_load' => true];

        if ($voucher->payment_type === 'NON RUTIN') {
            $event['crudTable-voucher_payment_non_rutin_create_success']      = true;
            $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
        } else {
            $event['crudTable-voucher_payment_rutin_create_success']      = true;
            $event['crudTable-voucher_payment_plan_rutin_create_success'] = true;
        }

        CustomHelper::voucherPayment($voucher);

        return $event;
    }

    // -------------------------------------------------------------------------
    // Destroy: hapus satu rencana pembayaran (delete chain + rollback log)
    // -------------------------------------------------------------------------

    public function destroy(int $id): array
    {
        $payment_voucher      = PaymentVoucher::findOrFail($id);
        $voucher              = Voucher::findOrFail($payment_voucher->voucher_id);
        $payment_voucher_plan = PaymentVoucherPlan::where('payment_voucher_id', $payment_voucher->id)->first();

        // Hapus approvals terkait plan
        Approval::where('model_type', 'App\\Models\\PaymentVoucherPlan')
            ->where('model_id', $payment_voucher_plan?->id)
            ->delete();

        $payment_voucher->delete();
        $payment_voucher_plan?->delete();

        // Rollback log pembayaran
        CustomVoid::rollbackPayment(Voucher::class, $voucher->id, 'CREATE_PLAN_PAYMENT_VOUCHER');
        CustomVoid::rollbackPayment(Voucher::class, $voucher->id, 'CREATE_PAYMENT_VOUCHER');

        $event = ['crudTable-filter_voucher_payment_plugin_load' => true];

        if ($voucher->payment_type === 'SUBKON') {
            $event['crudTable-voucher_payment_plan_subkon_create_success'] = true;
        } else {
            $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
        }

        return $event;
    }

    // -------------------------------------------------------------------------
    // Approved Store: proses approval satu rencana pembayaran
    // -------------------------------------------------------------------------

    public function approvedStore(int $id, VoucherPaymentPlanApproveData $dto): array
    {
        $user_id              = backpack_user()->id;
        $voucher_payment_plan = PaymentVoucherPlan::findOrFail($id);

        $approval = Approval::where('model_type', PaymentVoucherPlan::class)
            ->where('model_id', $voucher_payment_plan->id)
            ->where('user_id', $user_id)
            ->where('no_apprv', $dto->no_apprv)
            ->firstOrFail();

        $approval->status      = $dto->action;
        $approval->approved_at = Carbon::now();
        $approval->save();

        $event = [
            'crudTable-filter_voucher_payment_plugin_load'           => true,
            'crudTable-voucher_payment_plan_non_rutin_create_success' => true,
        ];

        return $event;
    }

    // -------------------------------------------------------------------------
    // Bulk Approve: approve banyak rencana sekaligus
    // -------------------------------------------------------------------------

    public function bulkApprove(VoucherPaymentPlanBulkApproveData $dto): array
    {
        $user_id        = backpack_user()->id;
        $approved_count = 0;
        $event          = [];

        foreach ($dto->entries as $entry) {
            $payment_plan_id = $entry['id'];
            $no_apprv        = $entry['no_apprv'] ?? null;

            $voucher_payment_plan = PaymentVoucherPlan::find($payment_plan_id);
            if (!$voucher_payment_plan) continue;

            $payment_voucher = PaymentVoucher::find($voucher_payment_plan->payment_voucher_id);
            if (!$payment_voucher) continue;

            // Set event berdasarkan payment_type
            if ($payment_voucher->voucher?->payment_type === 'SUBKON') {
                $event['crudTable-voucher_payment_plan_subkon_create_success'] = true;
            } else {
                $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
            }
            $event['crudTable-voucher_payment_plan_all_create_success'] = true;

            $approval = Approval::where('model_type', PaymentVoucherPlan::class)
                ->where('model_id', $voucher_payment_plan->id)
                ->where('user_id', $user_id)
                ->where('no_apprv', $no_apprv)
                ->first();

            // Skip jika tidak ada approval atau sudah bukan Pending
            if (!$approval || $approval->status !== 'Pending') continue;

            // Cek sequential approval: tahap sebelumnya harus sudah approve
            if ($no_apprv > 1) {
                $prev_unapproved = Approval::where('model_type', PaymentVoucherPlan::class)
                    ->where('model_id', $voucher_payment_plan->id)
                    ->where('no_apprv', '<', $no_apprv)
                    ->where('status', '!=', Approval::APPROVED)
                    ->count();

                if ($prev_unapproved > 0) continue;
            }

            $approval->status      = Approval::APPROVED;
            $approval->approved_at = Carbon::now();
            $approval->save();

            $approved_count++;
        }

        $event['crudTable-filter_voucher_payment_plugin_load'] = true;

        return [
            'event'          => $event,
            'approved_count' => $approved_count,
        ];
    }

    // -------------------------------------------------------------------------
    // Bulk Delete: hapus banyak rencana sekaligus
    // -------------------------------------------------------------------------

    public function bulkDelete(VoucherPaymentPlanBulkDeleteData $dto): array
    {
        $deleted_count = 0;
        $event         = ['crudTable-filter_voucher_payment_plugin_load' => true];

        foreach ($dto->entries as $payment_plan_id) {
            $payment_voucher_plan = PaymentVoucherPlan::find($payment_plan_id);
            if (!$payment_voucher_plan) continue;

            $payment_voucher = PaymentVoucher::find($payment_voucher_plan->payment_voucher_id);
            if (!$payment_voucher) continue;

            $voucher = Voucher::find($payment_voucher->voucher_id);
            if ($voucher) {
                if ($voucher->payment_type === 'SUBKON') {
                    $event['crudTable-voucher_payment_plan_subkon_create_success'] = true;
                } else {
                    $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
                }
                $event['crudTable-voucher_payment_plan_all_create_success'] = true;
            }

            Approval::where('model_type', 'App\Models\PaymentVoucherPlan')
                ->where('model_id', $payment_voucher_plan->id)
                ->delete();

            $payment_voucher->delete();
            $payment_voucher_plan->delete();

            if ($voucher) {
                CustomVoid::rollbackPayment(Voucher::class, $voucher->id, 'CREATE_PLAN_PAYMENT_VOUCHER');
                CustomVoid::rollbackPayment(Voucher::class, $voucher->id, 'CREATE_PAYMENT_VOUCHER');
            }

            $deleted_count++;
        }

        return [
            'event'         => $event,
            'deleted_count' => $deleted_count,
        ];
    }
}
