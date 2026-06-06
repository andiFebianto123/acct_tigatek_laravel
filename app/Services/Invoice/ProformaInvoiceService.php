<?php

namespace App\Services\Invoice;

use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceDetail;
use App\DTOs\Invoice\ProformaInvoiceSaveData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProformaInvoiceService
{
    public function createInvoice(ProformaInvoiceSaveData $dto): ProformaInvoice
    {
        return DB::transaction(function () use ($dto) {
            $total_price = $this->calculateTotalPrice($dto);
            $calculation = $this->calculateCalculation($dto->nominal_exclude_ppn, $dto->tax_ppn, $dto->pph);

            $invoice = new ProformaInvoice();
            $this->mapDtoToModel($invoice, $dto, $total_price, $calculation['diskon_pph']);

            if ($dto->invoice_document) {
                $filename = time() . '_' . $dto->invoice_document->getClientOriginalName();
                $path = $dto->invoice_document->storeAs('document_invoice', $filename, 'public');
                $invoice->invoice_document = $path;
            }

            $invoice->status = 'Unpaid';
            $invoice->save();

            $this->saveDetails($invoice, $dto->proforma_invoice_details);

            return $invoice;
        });
    }

    public function updateInvoice(int $id, ProformaInvoiceSaveData $dto): ProformaInvoice
    {
        return DB::transaction(function () use ($id, $dto) {
            $invoice = ProformaInvoice::findOrFail($id);

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

            ProformaInvoiceDetail::where('proforma_invoice_id', $id)->delete();
            $this->saveDetails($invoice, $dto->proforma_invoice_details);

            return $invoice;
        });
    }

    public function deleteInvoice(int $id): void
    {
        DB::transaction(function () use ($id) {
            $invoice = ProformaInvoice::findOrFail($id);
            $invoice->delete();
        });
    }

    private function mapDtoToModel(ProformaInvoice $invoice, ProformaInvoiceSaveData $dto, float $total_price, float $diskon_pph): void
    {
        $invoice->invoice_number = $dto->invoice_number;
        $invoice->name = 'proforma';
        $invoice->address_po = $dto->address_po ?? '';
        $invoice->description = $dto->description;
        $invoice->invoice_date = $dto->invoice_date;
        $invoice->client_po_id = $dto->client_po_id;
        $invoice->tax_ppn = $dto->tax_ppn;
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
        $invoice->subkon_id = $dto->subkon_id;
    }

    private function saveDetails(ProformaInvoice $invoice, array $details): void
    {
        foreach ($details as $item) {
            $price = (float) str_replace('.', '', (string) ($item['price'] ?? 0));
            if ($price > 0 || !empty($item['name'])) {
                $invoice_item = new ProformaInvoiceDetail();
                $invoice_item->proforma_invoice_id = $invoice->id;
                $invoice_item->name = $item['name'] ?? '';
                $invoice_item->qty = (int) ($item['qty'] ?? 1);
                $invoice_item->price = $price;
                $invoice_item->save();
            }
        }
    }

    private function calculateTotalPrice(ProformaInvoiceSaveData $dto): float
    {
        $total = $dto->nominal_include_ppn;
        foreach ($dto->proforma_invoice_details as $item) {
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
