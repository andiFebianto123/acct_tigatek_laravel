<?php

namespace App\Services\Fa;

use App\DTOs\Fa\VoucherData;
use App\Models\Voucher;
use App\Models\VoucherEdit;
use App\Models\Approval;
use App\Models\ClientPo;
use App\Models\PurchaseOrder;
use App\Models\Spk;
use App\Models\Subkon;
use App\Models\User;
use App\Models\LogPayment;
use App\Http\Helpers\CustomVoid;
use App\Http\Helpers\CustomHelper;
use App\Models\AccountTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherService
{
    public function calculatePayment(array $inputs): array
    {
        $billValue = (float) ($inputs['bill_value'] ?? 0);
        $ppn       = (float) ($inputs['tax_ppn'] ?? 0);
        $pph23     = (float) ($inputs['pph_23'] ?? 0);
        $pph4      = (float) ($inputs['pph_4'] ?? 0);
        $pph21     = (float) ($inputs['pph_21'] ?? 0);

        $nilaiPpn = ($ppn == 0) ? 0 : ($billValue * ($ppn / 100));
        $total    = $billValue + $nilaiPpn;

        $diskonPph23 = ($pph23 == 0) ? 0 : $billValue * ($pph23 / 100);
        $diskonPph4  = ($pph4  == 0) ? 0 : $billValue * ($pph4  / 100);
        $diskonPph21 = ($pph21 == 0) ? 0 : $billValue * ($pph21 / 100);

        $paymentTransfer = $total - $diskonPph23 - $diskonPph4 - $diskonPph21;

        return [
            'bill_value'               => $billValue,
            'nilai_ppn'                => $nilaiPpn,
            'total'                    => $total,
            'diskon_pph_23'            => $diskonPph23,
            'diskon_pph_4'             => $diskonPph4,
            'diskon_pph_21'            => $diskonPph21,
            'payment_transfer'         => $paymentTransfer,
        ];
    }

    public function createVoucher(VoucherData $data, $request): Voucher
    {
        $hasilPerhitungan = $this->calculatePayment([
            'bill_value' => $data->bill_value,
            'tax_ppn' => $data->tax_ppn,
            'pph_23' => $data->pph_23,
            'pph_4' => $data->pph_4,
            'pph_21' => $data->pph_21,
        ]);

        $type = strtolower($data->type ?? '');

        $item = new Voucher;
        $item->company_id = $data->company_id;
        $item->no_payment = $data->no_payment;
        $item->account_id  = $data->account_id;
        $item->account_source_id = $data->account_source_id;
        if ($type == 'client') {
            $item->reference_type = ClientPo::class;
        } else if ($type == 'subkon') {
            $item->reference_type = PurchaseOrder::class;
        } else if ($type == 'spk') {
            $item->reference_type = Spk::class;
        }
        $item->subkon_id = $data->subkon_id;
        $item->client_po_id = $data->client_po_id;

        $item->reference_id = $data->reference_id;
        $item->no_voucher = $data->no_voucher;
        $item->work_code = '';
        $item->job_name = $data->job_name;
        $item->for_voucher = '';
        $item->date_voucher = $data->date_voucher;
        $item->bussines_entity_code = '';
        $item->bussines_entity_type = '';
        $item->bussines_entity_name = '';
        $item->bill_number = $data->bill_number;
        $item->bill_date = $data->bill_date;
        $item->date_receipt_bill = $data->date_receipt_bill;
        $item->payment_description = $data->payment_description;
        $item->no_po_spk = $data->client_po_id;
        $item->date_po_spk = null;
        $item->bill_value = $data->bill_value;
        $item->dpp_value = $request->dpp_value ?? 0;
        $item->tax_ppn = $data->tax_ppn;
        $item->total = $hasilPerhitungan['total'];
        $item->pph_23 = $data->pph_23;
        $item->discount_pph_23 = $hasilPerhitungan['diskon_pph_23'];
        $item->pph_4 = $data->pph_4;
        $item->discount_pph_4 = $hasilPerhitungan['diskon_pph_4'];
        $item->pph_21 = $data->pph_21;
        $item->discount_pph_21 = $hasilPerhitungan['diskon_pph_21'];
        $item->payment_transfer = $hasilPerhitungan['payment_transfer'];
        $item->due_date = $data->due_date;
        $item->factur_status = $data->factur_status;
        $item->no_factur = $data->no_factur;
        $item->date_factur = $data->factur_date;

        $castAccount = Subkon::find($data->subkon_id);
        if ($castAccount) {
            $item->bank_name = $castAccount->bank_name;
            $item->no_account = $castAccount->bank_account;
            $item->account_holder_name = $castAccount->account_holder_name;
        }

        $item->payment_type = $data->payment_type;
        $item->payment_status = "BELUM BAYAR";
        $item->payment_date = null;
        $item->priority = $request->priority;
        $item->information = $request->information ?? '';
        $item->save();

        $voucher_edit = new VoucherEdit;
        $voucher_edit->voucher_id = $item->id;
        $voucher_edit->user_id  = backpack_auth()->user()->id;
        $voucher_edit->date_update = Carbon::now();
        $voucher_edit->history_update = "Menambahkan data voucher baru";
        $voucher_edit->save();

        $users = User::permission('APPROVE VOUCHER')
            ->orderBy('no_order', 'ASC')->get();

        foreach ($users as $key => $user) {
            $approval = new Approval;
            $approval->model_type = VoucherEdit::class;
            $approval->model_id = $voucher_edit->id;
            $approval->no_apprv = $key + 1;
            $approval->user_id = $user->id;
            $approval->position = '';
            $approval->status = Approval::PENDING;
            $approval->save();
        }

        if ($item->reference_type == ClientPo::class) {
            if ($item->reference && $item->reference->status == 'TANPA PO') {
                $client = ClientPo::find($item->reference_id);
                if ($client) {
                    $client->job_name = $item->job_name;
                    $client->load_general_value = $item->payment_transfer;
                    $client->save();
                }
            }
        }

        CustomVoid::voucherCreate($item);
        CustomVoid::voucherAllPph($item);

        return $item;
    }

    public function updateVoucher(int $id, VoucherData $data, $request): Voucher
    {
        $hasilPerhitungan = $this->calculatePayment([
            'bill_value' => $data->bill_value,
            'tax_ppn' => $data->tax_ppn,
            'pph_23' => $data->pph_23,
            'pph_4' => $data->pph_4,
            'pph_21' => $data->pph_21,
        ]);

        $oldItem = DB::table('vouchers')->where('id', $id)->first();
        $item = Voucher::findOrFail($id);

        $type = strtolower($data->type ?? '');

        $item->company_id = $data->company_id;
        $item->no_payment = $data->no_payment;
        $item->account_id  = $data->account_id;
        $item->account_source_id = $data->account_source_id;
        if ($type == 'client') {
            $item->reference_type = ClientPo::class;
        } else if ($type == 'subkon') {
            $item->reference_type = PurchaseOrder::class;
        } else if ($type == 'spk') {
            $item->reference_type = Spk::class;
        }
        $item->subkon_id = $data->subkon_id;
        $item->client_po_id = $data->client_po_id;

        $item->reference_id = $data->reference_id;
        $item->no_voucher = $data->no_voucher;
        $item->work_code = '';
        $item->job_name = $data->job_name;
        $item->for_voucher = '';
        $item->date_voucher = $data->date_voucher;
        $item->bussines_entity_code = '';
        $item->bussines_entity_type = '';
        $item->bussines_entity_name = '';
        $item->bill_number = $data->bill_number;
        $item->bill_date = $data->bill_date;
        $item->date_receipt_bill = $data->date_receipt_bill;
        $item->payment_description = $data->payment_description;
        $item->no_po_spk = '';
        $item->date_po_spk = null;
        $item->bill_value = $data->bill_value;
        $item->dpp_value = $request->dpp_value ?? 0;
        $item->tax_ppn = $data->tax_ppn;
        $item->total = $hasilPerhitungan['total'];
        $item->pph_23 = $data->pph_23;
        $item->discount_pph_23 = $hasilPerhitungan['diskon_pph_23'];
        $item->pph_4 = $data->pph_4;
        $item->discount_pph_4 = $hasilPerhitungan['diskon_pph_4'];
        $item->pph_21 = $data->pph_21;
        $item->discount_pph_21 = $hasilPerhitungan['diskon_pph_21'];
        $item->payment_transfer = $hasilPerhitungan['payment_transfer'];
        $item->due_date = $data->due_date;
        $item->factur_status = $data->factur_status;
        $item->no_factur = $data->no_factur;
        $item->date_factur = $data->factur_date;

        $castAccount = Subkon::find($data->subkon_id);
        if ($castAccount) {
            $item->bank_name = $castAccount->bank_name;
            $item->no_account = $castAccount->bank_account;
            $item->account_holder_name = $castAccount->account_holder_name;
        }

        $item->payment_type = $data->payment_type;
        $item->payment_status = $data->payment_status;
        $item->payment_date = $data->payment_date;
        $item->priority = $request->priority;
        $item->information = $request->information ?? '';

        $fieldsToLog = [
            'no_payment', 'account_id', 'reference_type', 'reference_id', 'no_voucher',
            'work_code', 'job_name', 'for_voucher', 'date_voucher', 'bussines_entity_code',
            'bussines_entity_type', 'bussines_entity_name', 'bill_number', 'bill_date',
            'date_receipt_bill', 'payment_description', 'date_po_spk', 'bill_value',
            'dpp_value', 'tax_ppn', 'total', 'pph_23', 'discount_pph_23', 'pph_4',
            'discount_pph_4', 'pph_21', 'discount_pph_21', 'payment_transfer', 'due_date',
            'factur_status', 'no_factur', 'date_factur', 'bank_name', 'no_account',
            'payment_type', 'payment_status', 'priority', 'information',
            'account_source_id', 'subkon_id', 'client_po_id', 'payment_date', 'account_holder_name',
        ];

        $edit_flag = 0;
        $edit_field = [];

        foreach ($fieldsToLog as $field) {
            $old = optional($oldItem)->$field;
            $new = $item->$field;

            $normalizedOld = $this->normalize($old);
            $normalizedNew = $this->normalize($new);

            if ($normalizedOld !== $normalizedNew) {
                $edit_field[] = $field;
                $edit_flag++;
            }
        }

        if ($edit_flag > 0) {
            $voucher_edit = new VoucherEdit;
            $voucher_edit->voucher_id = $item->id;
            $voucher_edit->user_id  = backpack_auth()->user()->id;
            $voucher_edit->date_update = Carbon::now();
            $voucher_edit->history_update = "Mengedit data voucher";
            $voucher_edit->save();

            $users = User::permission(['APPROVE EDIT VOUCHER'])
                ->orderBy('no_order', 'ASC')->get();

            foreach ($users as $key => $user) {
                $approval = new Approval;
                $approval->model_type = VoucherEdit::class;
                $approval->model_id = $voucher_edit->id;
                $approval->no_apprv = $key + 1;
                $approval->user_id = $user->id;
                $approval->position = '';
                $approval->status = Approval::PENDING;
                $approval->save();
            }
        }

        if ($item->reference_type == ClientPo::class) {
            if ($item->reference && $item->reference->status == 'TANPA PO') {
                $client = ClientPo::find($item->reference_id);
                if ($client) {
                    $client->job_name = $item->job_name;
                    $client->load_general_value = $item->payment_transfer;
                    $client->save();
                }
            }
        }

        $field_danger = [
            "account_id", "bill_value", "total", "pph_23", "discount_pph_23",
            "pph_4", "discount_pph_4", "pph_21", "discount_pph_21", "payment_transfer",
            "payment_status", "account_source_id", "payment_date"
        ];
        $flag_validation_field = false;

        foreach ($field_danger as $name_field) {
            if (in_array($name_field, $edit_field)) {
                $flag_validation_field = true;
                break;
            }
        }

        if ($flag_validation_field) {
            CustomVoid::rollbackPayment(Voucher::class, $item->id);
            CustomVoid::voucherCreate($item);
            CustomVoid::voucherAllPph($item);
            $item->payment_status = 'BELUM BAYAR';
            $item->payment_date = null;
        }

        $item->save();

        return $item;
    }

    public function deleteVoucher(int $id): void
    {
        $item = Voucher::findOrFail($id);

        $voucher_edit = VoucherEdit::where('voucher_id', $id)->get();

        foreach ($voucher_edit as $edit_v) {
            Approval::where('model_type', VoucherEdit::class)
                ->where('model_id', $edit_v->id)->delete();
            $edit_v->delete();
        }

        // hapus transaksi voucher
        CustomHelper::rollbackPayment(Voucher::class, $id);

        $item->delete();
    }

    public function voidPayment(int $id): array
    {
        $voucher = Voucher::findOrFail($id);

        // Check if log exists
        $log = LogPayment::where('reference_type', 'App\Models\Voucher')
            ->where('reference_id', $id)
            ->where('name', 'CREATE_PAYMENT_VOUCHER')
            ->first();

        if (!$log) {
            return [
                'success' => false,
                'message' => 'Log pembayaran tidak ditemukan.'
            ];
        }

        // Rollback payment logic
        CustomVoid::rollbackPayment('App\Models\Voucher', $id, 'CREATE_PAYMENT_VOUCHER');

        return [
            'success' => true,
            'message' => 'Pembayaran voucher berhasil di-Void.'
        ];
    }

    public function addTransaction(int $id): int
    {
        $voucher = Voucher::find($id);
        if (!$voucher) return 0;

        $transaksi = new AccountTransaction;
        $transaksi->cast_account_id = $voucher->account_source_id;
        $transaksi->reference_type = Voucher::class;
        $transaksi->reference_id = $id;
        $transaksi->date_transaction = Carbon::now()->format('Y-m-d');
        $transaksi->nominal_transaction = $voucher->payment_transfer;
        $transaksi->total_saldo_before = 0;
        $transaksi->total_saldo_after = 0;
        $transaksi->status = \App\Models\CastAccount::ENTER;
        $transaksi->kdp = $voucher?->reference?->work_code;
        $transaksi->job_name = $voucher?->reference?->job_name;
        $transaksi->save();

        CustomHelper::updateOrCreateJournalEntry([
            'account_id' => $voucher->account_id,
            'reference_id' => $transaksi->id,
            'reference_type' => AccountTransaction::class,
            'description' => $transaksi->kdp,
            'date' => Carbon::now(),
            'debit' => $voucher->payment_transfer,
        ], [
            'account_id' => $voucher->account_id,
            'reference_id' => $transaksi->id,
            'reference_type' => AccountTransaction::class,
        ]);

        return 1;
    }

    public function approveVoucherEdit(int $id, int $userId, array $requestData): Approval
    {
        $voucherEdit = VoucherEdit::findOrFail($id);

        $approvalBefore = Approval::where('model_type', VoucherEdit::class)
            ->where('model_id', $voucherEdit->id)
            ->where('no_apprv', '<', $requestData['no_apprv'])
            ->first();

        if ($approvalBefore) {
            if ($approvalBefore->status != Approval::APPROVED) {
                throw new \Exception('Approval sebelumnya belum disetujui');
            }
        }

        $approval = Approval::where('model_type', VoucherEdit::class)
            ->where('model_id', $voucherEdit->id)
            ->where('user_id', $userId)
            ->where('no_apprv', $requestData['no_apprv'])
            ->first();

        if (!$approval) {
            throw new \Exception('Data approval tidak ditemukan');
        }

        $approval->status = $requestData['action'];
        $approval->approved_at = Carbon::now();
        $approval->save();

        return $approval;
    }

    private function normalize($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        return (string) $value;
    }
}
