<?php

namespace App\Services\Invoice;

use App\Models\ProformaInvoiceClient;
use App\Models\ProformaInvoiceClientDetail;
use App\DTOs\Invoice\ProformaInvoiceClientSaveData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProformaInvoiceClientService
{
    public function createInvoice(ProformaInvoiceClientSaveData $dto): ProformaInvoiceClient
    {
        return DB::transaction(function () use ($dto) {
            $total_price = $this->calculateTotalPrice($dto);
            $calculation = $this->calculateCalculation($dto->nominal_exclude_ppn, $dto->tax_ppn, $dto->pph);

            $invoice = new ProformaInvoiceClient();
            $this->mapDtoToModel($invoice, $dto, $total_price, $calculation['diskon_pph']);

            if ($dto->invoice_document) {
                $filename = time() . '_' . $dto->invoice_document->getClientOriginalName();
                $path = $dto->invoice_document->storeAs('document_invoice', $filename, 'public');
                $invoice->invoice_document = $path;
            }

            $invoice->status = 'Unpaid';
            $invoice->save();

            $this->saveDetails($invoice, $dto->proforma_invoice_client_details);

            return $invoice;
        });
    }

    public function updateInvoice(int $id, ProformaInvoiceClientSaveData $dto): ProformaInvoiceClient
    {
        return DB::transaction(function () use ($id, $dto) {
            $invoice = ProformaInvoiceClient::findOrFail($id);

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

            ProformaInvoiceClientDetail::where('proforma_invoice_client_id', $id)->delete();
            $this->saveDetails($invoice, $dto->proforma_invoice_client_details);

            return $invoice;
        });
    }

    public function deleteInvoice(int $id): void
    {
        DB::transaction(function () use ($id) {
            $invoice = ProformaInvoiceClient::findOrFail($id);
            if ($invoice->invoice_document && Storage::disk('public')->exists($invoice->invoice_document)) {
                Storage::disk('public')->delete($invoice->invoice_document);
            }
            $invoice->delete();
        });
    }

    private function mapDtoToModel(ProformaInvoiceClient $invoice, ProformaInvoiceClientSaveData $dto, float $total_price, float $diskon_pph): void
    {
        $currencyCode = $dto->currency_code ?? 'IDR';
        $usdRate = \App\Models\Setting::first()?->usd_rate ?? 16000;
        $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

        $invoice->currency_code = $currencyCode;
        $invoice->exchange_rate = $exchangeRate;
        $invoice->price_total_exclude_ppn_base = $dto->nominal_exclude_ppn * $exchangeRate;
        $invoice->price_total_include_ppn_base = $dto->nominal_include_ppn * $exchangeRate;
        $invoice->discount_pph_base = $diskon_pph * $exchangeRate;

        $invoice->invoice_number = $dto->invoice_number;
        $invoice->name = 'proforma';
        $invoice->address_po = $dto->address_po ?? '';
        $invoice->description = $dto->description;
        $invoice->invoice_date = $dto->invoice_date;
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
        $invoice->note = $dto->note;
        $invoice->type_device = $dto->type_device;
        $invoice->term = $dto->term;
    }

    private function parseItemPrice(mixed $val, string $currencyCode): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }
        $str = (string) ($val ?? 0);
        if ($currencyCode === 'USD') {
            return (float) str_replace(',', '', $str);
        }
        return (float) str_replace('.', '', $str);
    }

    private function saveDetails(ProformaInvoiceClient $invoice, array $details): void
    {
        $currencyCode = $invoice->currency_code ?? 'IDR';
        $exchangeRate = (float) ($invoice->exchange_rate ?? 1.0);

        foreach ($details as $item) {
            $price = $this->parseItemPrice($item['price'] ?? 0, $currencyCode);
            if ($price > 0 || !empty($item['name'])) {
                $invoice_item = new ProformaInvoiceClientDetail();
                $invoice_item->proforma_invoice_client_id = $invoice->id;
                $invoice_item->name = $item['name'] ?? '';
                $invoice_item->qty = (int) ($item['qty'] ?? 1);
                $invoice_item->price = $price;
                $invoice_item->price_base = $price * $exchangeRate;
                $invoice_item->save();
            }
        }
    }

    private function calculateTotalPrice(ProformaInvoiceClientSaveData $dto): float
    {
        $currencyCode = $dto->currency_code ?? 'IDR';
        $total = $dto->nominal_include_ppn;
        foreach ($dto->proforma_invoice_client_details as $item) {
            $price = $this->parseItemPrice($item['price'] ?? 0, $currencyCode);
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
