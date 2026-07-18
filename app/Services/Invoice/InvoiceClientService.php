<?php

namespace App\Services\Invoice;

use App\DTOs\Invoice\InvoiceClientSaveData;
use App\Http\Helpers\CustomVoid;
use App\Models\ClientPo;
use App\Models\InvoiceClient;
use App\Models\InvoiceClientDetail;
use App\Models\LogPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceClientService
{
    public function createInvoice(InvoiceClientSaveData $dto): InvoiceClient
    {
        return DB::transaction(function () use ($dto) {
            $originalPo = $dto->client_po_id ? \App\Models\ClientPo::find($dto->client_po_id) : null;
            
            $clientId = null;
            $startDate = $dto->invoice_date;
            $endDate = $dto->invoice_date;
            $reimburseType = 'reimburse';
            $category = 'general';
            $rapValue = 0;
            $priceTotal = 0;
            $profitAndLoss = 0;

            if ($originalPo) {
                $clientId = $originalPo->client_id;
                $startDate = $originalPo->start_date;
                $endDate = $originalPo->end_date;
                $reimburseType = $originalPo->reimburse_type;
                $category = $originalPo->category;
                $rapValue = $originalPo->rap_value;
                $priceTotal = $originalPo->price_total;
                $profitAndLoss = $originalPo->profit_and_loss;
            } else {
                $client = \App\Models\Client::where('company_id', $dto->company_id)->first();
                if ($client) {
                    $clientId = $client->id;
                }
            }

            if ($clientId) {
                $newPo = new \App\Models\ClientPo();
                $newPo->client_id = $clientId;
                $newPo->company_id = $dto->company_id;

                // Auto generate work_code
                do {
                    $workCode = 'WRK-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));
                } while (\App\Models\ClientPo::where('work_code', $workCode)->exists());
                $newPo->work_code = $workCode;

                // Auto generate po_number
                do {
                    $poNumber = 'PO-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));
                } while (\App\Models\ClientPo::where('po_number', $poNumber)->exists());
                $newPo->po_number = $poNumber;

                $newPo->job_name = $dto->description; // job_name samakan dengan description di invoice_client
                $newPo->job_value = $dto->nominal_exclude_ppn; // nilai job_value isi sesuai Nominal Exclude PPn
                $newPo->tax_ppn = $dto->tax_ppn; // nilai tax_ppn samakan dengan tax_ppn
                $newPo->job_value_include_ppn = $dto->nominal_include_ppn; // job_value_include_ppn disamakan dengan price_total_include_ppn
                $newPo->date_po = $dto->invoice_date;
                $newPo->status = "ADA PO";

                $newPo->start_date = $startDate;
                $newPo->end_date = $endDate;
                $newPo->reimburse_type = $reimburseType;
                $newPo->category = $category;
                $newPo->rap_value = $rapValue;
                $newPo->price_total = $priceTotal;
                $newPo->profit_and_loss = $profitAndLoss;
                $newPo->save();

                // Create a new DTO with the new client_po_id and work_code as kdp
                $dto = new InvoiceClientSaveData(
                    invoice_number: $dto->invoice_number,
                    description: $dto->description,
                    invoice_date: $dto->invoice_date,
                    client_po_id: $newPo->id,
                    nominal_exclude_ppn: $dto->nominal_exclude_ppn,
                    nominal_include_ppn: $dto->nominal_include_ppn,
                    tax_ppn: $dto->tax_ppn,
                    pph: $dto->pph,
                    dpp_other: $dto->dpp_other,
                    kdp: $newPo->work_code,
                    withholding_agent: $dto->withholding_agent,
                    send_invoice_normal: $dto->send_invoice_normal,
                    send_invoice_revision: $dto->send_invoice_revision,
                    address_po: $dto->address_po,
                    invoice_client_details: $dto->invoice_client_details,
                    company_id: $dto->company_id,
                    invoice_document: $dto->invoice_document,
                    account_source_id: $dto->account_source_id,
                    type_device: $dto->type_device,
                    term: $dto->term,
                    client_id: $clientId
                );
            }

            $total_price = $this->calculateTotalPrice($dto);
            $calculation = $this->calculateCalculation($dto->nominal_exclude_ppn, $dto->tax_ppn, $dto->pph);

            $invoice = new InvoiceClient();
            $this->mapDtoToModel($invoice, $dto, $total_price, $calculation['diskon_pph']);
            if ($clientId) {
                $invoice->client_id = $clientId;
            }

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

            // Update the associated Client PO if it exists
            $po = $dto->client_po_id ? \App\Models\ClientPo::find($dto->client_po_id) : null;
            if ($po) {
                $po->job_name = $dto->description;
                $po->job_value = $dto->nominal_exclude_ppn;
                $po->tax_ppn = $dto->tax_ppn;
                $po->job_value_include_ppn = $dto->nominal_include_ppn;
                $po->date_po = $dto->invoice_date;
                $po->status = "ADA PO";
                $po->save();
            } else {
                $client = \App\Models\Client::where('company_id', $dto->company_id)->first();
                if ($client) {
                    $po = new \App\Models\ClientPo();
                    $po->client_id = $client->id;
                    $po->company_id = $dto->company_id;

                    // Auto generate work_code
                    do {
                        $workCode = 'WRK-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));
                    } while (\App\Models\ClientPo::where('work_code', $workCode)->exists());
                    $po->work_code = $workCode;

                    // Auto generate po_number
                    do {
                        $poNumber = 'PO-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));
                    } while (\App\Models\ClientPo::where('po_number', $poNumber)->exists());
                    $po->po_number = $poNumber;

                    $po->job_name = $dto->description;
                    $po->job_value = $dto->nominal_exclude_ppn;
                    $po->tax_ppn = $dto->tax_ppn;
                    $po->job_value_include_ppn = $dto->nominal_include_ppn;

                    $po->start_date = $dto->invoice_date;
                    $po->end_date = $dto->invoice_date;
                    $po->reimburse_type = 'reimburse';
                    $po->category = 'general';
                    $po->save();

                    // Re-create DTO with new PO details
                    $dto = new InvoiceClientSaveData(
                        invoice_number: $dto->invoice_number,
                        description: $dto->description,
                        invoice_date: $dto->invoice_date,
                        client_po_id: $po->id,
                        nominal_exclude_ppn: $dto->nominal_exclude_ppn,
                        nominal_include_ppn: $dto->nominal_include_ppn,
                        tax_ppn: $dto->tax_ppn,
                        pph: $dto->pph,
                        dpp_other: $dto->dpp_other,
                        kdp: $po->work_code,
                        withholding_agent: $dto->withholding_agent,
                        send_invoice_normal: $dto->send_invoice_normal,
                        send_invoice_revision: $dto->send_invoice_revision,
                        address_po: $dto->address_po,
                        invoice_client_details: $dto->invoice_client_details,
                        company_id: $dto->company_id,
                        invoice_document: $dto->invoice_document,
                        account_source_id: $dto->account_source_id,
                        type_device: $dto->type_device,
                        term: $dto->term,
                        client_id: $client->id
                    );
                }
            }

            $total_price = $this->calculateTotalPrice($dto);
            $calculation = $this->calculateCalculation($dto->nominal_exclude_ppn, $dto->tax_ppn, $dto->pph);

            $this->mapDtoToModel($invoice, $dto, $total_price, $calculation['diskon_pph']);
            if ($po) {
                $invoice->client_id = $po->client_id;
            }

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
            $clientPo = ClientPo::where('id', $invoice->client_po_id)->first();
            CustomVoid::invoiceDelete($invoice);
            if ($clientPo) {
                $clientPo->delete();
            }
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
        $invoice->type_device = $dto->type_device;
        $invoice->term = $dto->term;
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
