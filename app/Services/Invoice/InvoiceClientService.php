<?php

namespace App\Services\Invoice;

use App\Models\InvoiceClient;
use App\Models\InvoiceClientDetail;
use App\Models\LogPayment;
use App\DTOs\Invoice\InvoiceClientSaveData;
use App\Http\Helpers\CustomVoid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceClientService
{
    public function createInvoice(InvoiceClientSaveData $dto): InvoiceClient
    {
        return DB::transaction(function () use ($dto) {
            $total_price = $this->calculateTotalPrice($dto);
            $calculation = $this->calculateCalculation($dto->nominal_exclude_ppn, $dto->tax_ppn, $dto->pph);

            $invoice = new InvoiceClient();
            $this->mapDtoToModel($invoice, $dto, $total_price, $calculation['diskon_pph']);

            if ($dto->invoice_document) {
                $filename = time() . '_' . $dto->invoice_document->getClientOriginalName();
                $path = $dto->invoice_document->storeAs('document_invoice', $filename, 'public');
                $invoice->invoice_document = $path;
            }

            $invoice->status = 'Unpaid';
            $invoice->save();

            $this->saveDetails($invoice, $dto->invoice_client_details);

            CustomVoid::invoiceMakeVoucherMoveAccount($invoice);
            CustomVoid::invoiceCreate($invoice);

            return $invoice;
        });
    }

    public function updateInvoice(int $id, InvoiceClientSaveData $dto): InvoiceClient
    {
        return DB::transaction(function () use ($id, $dto) {
            $invoice = InvoiceClient::findOrFail($id);
            $old_client_po_id = $invoice->client_po_id;

            $total_price = $this->calculateTotalPrice($dto);
            $calculation = $this->calculateCalculation($dto->nominal_exclude_ppn, $dto->tax_ppn, $dto->pph);

            $this->mapDtoToModel($invoice, $dto, $total_price, $calculation['diskon_pph']);

            if ($dto->invoice_document) {
                if ($invoice->invoice_document && Storage::disk('public')->exists($invoice->invoice_document)) {
                    Storage::disk('public')->delete($invoice->invoice_document);
                }
                $filename = time() . '_' . $dto->invoice_document->getClientOriginalName();
                $path = $dto->invoice_document->storeAs('document_invoice', $filename, 'public');
                $invoice->invoice_document = $path;
            }

            $invoice->save();

            InvoiceClientDetail::where('invoice_client_id', $id)->delete();
            $this->saveDetails($invoice, $dto->invoice_client_details);

            if ($invoice->wasChanged([
                'price_total_exclude_ppn',
                'price_total_include_ppn',
                'tax_ppn',
                'pph',
                'price_dpp',
                'client_po_id',
                'withholding_agent'
            ])) {
                CustomVoid::invoiceUpdate($invoice, $old_client_po_id);
            }

            return $invoice;
        });
    }

    public function deleteInvoice(int $id): void
    {
        DB::transaction(function () use ($id) {
            $invoice = InvoiceClient::findOrFail($id);
            CustomVoid::invoiceDelete($invoice);
            $invoice->delete();
        });
    }

    public function voidInvoice(int $id): void
    {
        DB::transaction(function () use ($id) {
            $invoice = InvoiceClient::findOrFail($id);

            $log = LogPayment::where('reference_type', 'App\Models\InvoiceClient')
                ->where('reference_id', $id)
                ->where('name', 'CREATE_PAYMENT_INVOICE')
                ->first();

            if (!$log) {
                throw new \Exception('Log pembayaran tidak ditemukan.');
            }

            CustomVoid::rollbackPayment('App\Models\InvoiceClient', $id, 'CREATE_PAYMENT_INVOICE');
        });
    }

    private function mapDtoToModel(InvoiceClient $invoice, InvoiceClientSaveData $dto, float $total_price, float $diskon_pph): void
    {
        $invoice->invoice_number = $dto->invoice_number;
        $invoice->name = 'invoice';
        $invoice->address_po = $dto->address_po ?? '';
        $invoice->description = $dto->description;
        $invoice->invoice_date = $dto->invoice_date;
        $invoice->client_po_id = $dto->client_po_id;
        $invoice->tax_ppn = $dto->tax_ppn;
        $invoice->price_dpp = $dto->dpp_other;
        $invoice->kdp = $dto->kdp;
        $invoice->withholding_agent = $dto->withholding_agent;
        $invoice->send_invoice_normal_date = $dto->send_invoice_normal;
        $invoice->send_invoice_revision_date = $dto->send_invoice_revision;
        $invoice->price_total_exclude_ppn = $dto->nominal_exclude_ppn;
        $invoice->price_total_include_ppn = $dto->nominal_include_ppn;
        $invoice->price_total = $total_price - $diskon_pph;
        $invoice->pph = $dto->pph;
        $invoice->discount_pph = $diskon_pph;
        $invoice->company_id = $dto->company_id;
        $invoice->account_source_id = $dto->account_source_id;
    }

    private function saveDetails(InvoiceClient $invoice, array $details): void
    {
        foreach ($details as $item) {
            $price = (float) str_replace('.', '', (string) ($item['price'] ?? 0));
            if ($price > 0 || !empty($item['name'])) {
                $invoice_item = new InvoiceClientDetail();
                $invoice_item->invoice_client_id = $invoice->id;
                $invoice_item->name = $item['name'] ?? '';
                $invoice_item->qty = (int) ($item['qty'] ?? 1);
                $invoice_item->price = $price;
                $invoice_item->save();
            }
        }
    }

    private function calculateTotalPrice(InvoiceClientSaveData $dto): float
    {
        $total = $dto->nominal_include_ppn;
        foreach ($dto->invoice_client_details as $item) {
            $price = (float) str_replace('.', '', (string) ($item['price'] ?? 0));
            $qty = (int) ($item['qty'] ?? 1);
            $total += ($price * $qty);
        }
        return $total;
    }

    private function calculateCalculation(float $billValue, float $ppn, float $pph): array
    {
        $nilaiPpn = ($ppn == 0) ? 0 : ($billValue * ($ppn / 100));
        $total    = $billValue + $nilaiPpn;
        $diskonPph = ($pph == 0) ? 0 : $billValue * ($pph / 100);

        return [
            'nilai_ppn'  => $nilaiPpn,
            'total'      => $total,
            'diskon_pph' => $diskonPph,
        ];
    }
}
