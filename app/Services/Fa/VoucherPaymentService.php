<?php

namespace App\Services\Fa;

use Illuminate\Support\Facades\DB;
use App\Models\Voucher;
use App\Models\PaymentVoucher;
use App\Http\Helpers\CustomVoid;
use App\Http\Helpers\CustomHelper;
use App\DTOs\Fa\VoucherPaymentStoreData;
use App\DTOs\Fa\VoucherPaymentStoreSingleData;

class VoucherPaymentService
{
    public function store(VoucherPaymentStoreData $dto): array
    {
        $event = [];
        $event['crudTable-filter_voucher_payment_plugin_load'] = true;

        foreach ($dto->voucher as $id_v) {
            $voucherItem = Voucher::find($id_v);
            if (!$voucherItem) continue;

            $castAccount = $voucherItem->account_source;
            if ($voucherItem->payment_type == 'NON RUTIN') {
                $event['crudTable-voucher_payment_non_rutin_create_success'] = true;
                $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
            } else {
                $event['crudTable-voucher_payment_rutin_create_success'] = true;
                $event['crudTable-voucher_payment_plan_rutin_create_success'] = true;
            }

            CustomVoid::voucherPayment($voucherItem);
            $balance_out = CustomHelper::balanceAccount($castAccount->account->code);
            if ($balance_out < 0) {
                throw new \Exception(trans('backpack::crud.cash_account.field_transfer.errors.balance_not_enough', ['castname' => $castAccount->name]));
            }
        }

        return $event;
    }

    public function storeSingle(VoucherPaymentStoreSingleData $dto): array
    {
        $event = [];
        $event['crudTable-filter_voucher_payment_plugin_load'] = true;

        $voucher = Voucher::find($dto->id);
        if (!$voucher) {
            throw new \Exception('Voucher tidak ditemukan');
        }
        $castAccount = $voucher->account_source;
        if ($voucher->payment_type == 'NON RUTIN') {
            $event['crudTable-voucher_payment_non_rutin_create_success'] = true;
            $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
        } else {
            $event['crudTable-voucher_payment_rutin_create_success'] = true;
            $event['crudTable-voucher_payment_plan_rutin_create_success'] = true;
        }
        
        CustomVoid::voucherPayment($voucher, $dto->date);
        $balance_out = CustomHelper::balanceAccount($castAccount->account->code);
        if ($balance_out < 0) {
            throw new \Exception(trans('backpack::crud.cash_account.field_transfer.errors.balance_not_enough', ['castname' => $castAccount->name]));
        }

        return $event;
    }

    public function destroy(int $id): array
    {
        $event = [];
        $event['crudTable-filter_voucher_payment_plugin_load'] = true;

        $voucherItem = Voucher::find($id);
        if ($voucherItem) {
            if ($voucherItem->payment_type == 'NON RUTIN') {
                $event['crudTable-voucher_payment_non_rutin_create_success'] = true;
                $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
            } else {
                $event['crudTable-voucher_payment_rutin_create_success'] = true;
                $event['crudTable-voucher_payment_plan_rutin_create_success'] = true;
            }
        }

        CustomVoid::rollbackPayment(Voucher::class, $id, "CREATE_PAYMENT_VOUCHER");

        return $event;
    }

    public function voidPayment(int $id): array
    {
        $voucher = Voucher::findOrFail($id);
        $voucher_id = $voucher->id;

        $voucher->payment_status = 'BELUM BAYAR';
        $voucher->payment_date = null;
        $voucher->save();

        $event = [];
        $event['crudTable-filter_voucher_payment_plugin_load'] = true;

        if ($voucher->payment_type == 'NON RUTIN') {
            $event['crudTable-voucher_payment_non_rutin_create_success'] = true;
            $event['crudTable-voucher_payment_plan_non_rutin_create_success'] = true;
        } else {
            $event['crudTable-voucher_payment_rutin_create_success'] = true;
            $event['crudTable-voucher_payment_plan_rutin_create_success'] = true;
        }

        CustomVoid::rollbackPayment(Voucher::class, $voucher_id, "CREATE_PAYMENT_VOUCHER");

        return $event;
    }
}
