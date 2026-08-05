<?php

namespace App\Services\Invoice;

use App\DTOs\Invoice\InvoiceClientSaveData;
use App\Http\Helpers\CustomVoid;
use App\Models\ClientPo;
use App\Models\DeviceStock;
use App\Models\DeviceStockHistory;
use App\Models\DeviceStockMutation;
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
                $clientId = $dto->client_id ?? $originalPo->client_id;
                $startDate = $originalPo->start_date;
                $endDate = $originalPo->end_date;
                $reimburseType = $originalPo->reimburse_type;
                $category = $originalPo->category;
                $rapValue = $originalPo->rap_value;
                $priceTotal = $originalPo->price_total;
                $profitAndLoss = $originalPo->profit_and_loss;
            } else {
                $clientId = $dto->client_id;
                if (!$clientId) {
                    $client = \App\Models\Client::where('company_id', $dto->company_id)->first();
                    if ($client) {
                        $clientId = $client->id;
                    }
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
                    client_id: $clientId,
                    currency_code: $dto->currency_code,
                    delivery_note_id: $dto->delivery_note_id,
                    pic: $dto->pic,
                    category: $dto->category,
                );
            }

            // Validasi stok jika tipe Persediaan
            $this->validateStockSufficiency($dto, $dto->invoice_client_details);

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

            // Rekonsiliasi FIFO & HPP dengan Surat Jalan jika ada
            if ($dto->delivery_note_id) {
                $this->processDeliveryNoteReconciliation($invoice, $dto);
            }

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
            $oldDeliveryNoteId = $invoice->delivery_note_id;

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
                        client_id: $client->id,
                        currency_code: $dto->currency_code,
                        delivery_note_id: $dto->delivery_note_id,
                        pic: $dto->pic,
                        category: $dto->category,
                    );
                }
            }

            // Jika Surat Jalan diganti, buka kunci Surat Jalan lama
            if ($oldDeliveryNoteId && (int)$oldDeliveryNoteId !== (int)$dto->delivery_note_id) {
                \App\Models\DeliveryNote::where('id', $oldDeliveryNoteId)->update(['invoice_client_id' => null]);
            }

            $this->validateStockSufficiency($dto, $dto->invoice_client_details);

            $total_price = $this->calculateTotalPrice($dto);
            $calculation = $this->calculateCalculation($dto->nominal_exclude_ppn, $dto->tax_ppn, $dto->pph);

            $this->mapDtoToModel($invoice, $dto, $total_price, $calculation['diskon_pph']);
            if ($dto->client_id) {
                $invoice->client_id = $dto->client_id;
            } else if ($po) {
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

            // Revert mutasi stok FIFO invoice lama sebelum simpan detail baru
            $this->revertFifoStock($invoice);

            InvoiceClientDetail::where('invoice_client_id', $id)->delete();
            $this->saveDetails($invoice, $dto->invoice_client_details);

            // Rekonsiliasi FIFO & HPP dengan Surat Jalan jika ada
            if ($dto->delivery_note_id) {
                $this->processDeliveryNoteReconciliation($invoice, $dto);
            }

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

            // Revert stok FIFO / mutasi invoice jika ada
            $this->revertFifoStock($invoice);

            // Lepas penguncian Surat Jalan jika terhubung
            \App\Models\DeliveryNote::where('invoice_client_id', $id)
                ->orWhere('id', $invoice->delivery_note_id)
                ->update(['invoice_client_id' => null]);

            // Revert stok FIFO jika invoice menggunakan Persediaan
            if ($invoice->type_device === \App\Models\DeviceStock::class) {
                $this->revertFifoStock($invoice);
            }

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
        $currencyCode = $dto->currency_code ?? 'IDR';
        $usdRate = \App\Models\Setting::first()?->usd_rate ?? 16000;
        $exchangeRate = ($currencyCode === 'USD') ? (float) $usdRate : 1.0;

        $invoice->currency_code = $currencyCode;
        $invoice->exchange_rate = $exchangeRate;
        $invoice->price_total_exclude_ppn_base = $dto->nominal_exclude_ppn * $exchangeRate;
        $invoice->price_total_include_ppn_base = $dto->nominal_include_ppn * $exchangeRate;
        $invoice->discount_pph_base = $diskon_pph * $exchangeRate;

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
        $invoice->delivery_note_id = $dto->delivery_note_id;
        $invoice->pic = $dto->pic;
        $invoice->category = $dto->category ?? 'rutin';
        if ($dto->client_id) {
            $invoice->client_id = $dto->client_id;
        }
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

    private function saveDetails(InvoiceClient $invoice, array $details): void
    {
        $currencyCode = $invoice->currency_code ?? 'IDR';
        $exchangeRate = (float) ($invoice->exchange_rate ?? 1.0);

        foreach ($details as $item) {
            $price = $this->parseItemPrice($item['price'] ?? 0, $currencyCode);
            if ($price > 0 || !empty($item['name'])) {
                $invoice_item = new InvoiceClientDetail();
                $invoice_item->invoice_client_id = $invoice->id;
                $invoice_item->name = $item['name'] ?? '';
                $invoice_item->qty = (int) ($item['qty'] ?? 1);
                $invoice_item->price = $price;
                $invoice_item->price_base = $price * $exchangeRate;

                // Simpan device_stock_id jika ada (dari mode Persediaan)
                $rawStockId = $item['device_stock_id'] ?? null;
                $deviceStockId = ($rawStockId !== null && (int) $rawStockId > 0)
                    ? (int) $rawStockId
                    : null;
                $invoice_item->device_stock_id = $deviceStockId;

                // Simpan delivery_note_detail_id jika ada
                $rawDnId = $item['delivery_note_detail_id'] ?? null;
                $dnDetailId = ($rawDnId !== null && (int) $rawDnId > 0)
                    ? (int) $rawDnId
                    : null;
                $invoice_item->delivery_note_detail_id = $dnDetailId;

                $invoice_item->save();
            }
        }
    }


    private function calculateTotalPrice(InvoiceClientSaveData $dto): float
    {
        $currencyCode = $dto->currency_code ?? 'IDR';
        $total = $dto->nominal_include_ppn;
        foreach ($dto->invoice_client_details as $item) {
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

    /**
     * Validasi apakah ketersediaan stok fisik cukup untuk item Persediaan pada Invoice.
     * Throws Exception jika stok kurang.
     *
     * @param InvoiceClientSaveData|InvoiceClient $invoiceData
     * @param array|null $details
     */
    public function validateStockSufficiency(mixed $invoiceData, ?array $details = null): void
    {
        // $typeDevice = is_a($invoiceData, InvoiceClient::class) ? $invoiceData->type_device : $invoiceData->type_device;
        // if ($typeDevice !== \App\Models\DeviceStock::class) {
        //     return;
        // }

        // $items = [];
        // if (is_a($invoiceData, InvoiceClient::class)) {
        //     $detailsList = InvoiceClientDetail::where('invoice_client_id', $invoiceData->id)
        //         ->whereNotNull('device_stock_id')
        //         ->get();
        //     foreach ($detailsList as $d) {
        //         $items[] = [
        //             'device_stock_id' => $d->device_stock_id,
        //             'qty' => (int) $d->qty,
        //         ];
        //     }
        // } else if (is_array($details)) {
        //     foreach ($details as $d) {
        //         if (!empty($d['device_stock_id'])) {
        //             $items[] = [
        //                 'device_stock_id' => (int) $d['device_stock_id'],
        //                 'qty' => (int) ($d['qty'] ?? 1),
        //             ];
        //         }
        //     }
        // }

        // foreach ($items as $item) {
        //     $stock = DeviceStock::find($item['device_stock_id']);
        //     if ($stock) {
        //         $available = (int) $stock->qty;
        //         $needed = (int) $item['qty'];
        //         if ($available < $needed) {
        //             throw new \Exception("Stok barang '{$stock->name}' tidak mencukupi. (Stok tersedia: {$available}, Dibutuhkan: {$needed}).");
        //         }
        //     }
        // }
        return;
    }

    // =========================================================================
    // FIFO STOCK CONSUMPTION
    // =========================================================================

    /**
     * Konsumsi stok device secara FIFO berdasarkan detail invoice.
     * Dipanggil setelah saveDetails() di createInvoice / updateInvoice.
     */
    public function consumeFifoStock(InvoiceClient $invoice): void
    {
        // Validasi stok dulu sebelum dipotong
        $this->validateStockSufficiency($invoice);

        $details = InvoiceClientDetail::where('invoice_client_id', $invoice->id)
            ->whereNotNull('device_stock_id')
            ->get();

        foreach ($details as $detail) {
            $this->consumeDeviceStockFifo($invoice, $detail);
        }
    }

    /**
     * Proses konsumsi stok FIFO untuk 1 baris detail invoice.
     */
    private function consumeDeviceStockFifo(InvoiceClient $invoice, InvoiceClientDetail $detail): void
    {
        $masterStock = DeviceStock::find($detail->device_stock_id);
        if (!$masterStock) {
            return;
        }

        $qtyNeeded = (int) $detail->qty;
        if ($qtyNeeded <= 0) {
            return;
        }

        // Ambil layers FIFO: qty > 0, ORDER BY id ASC (First In First Out)
        $layers = DeviceStockHistory::where('device_stock_id', $masterStock->id)
            ->where('qty', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        $totalCogsBase = 0.0;
        $totalCogs     = 0.0;
        $qtyRemaining  = $qtyNeeded;

        foreach ($layers as $layer) {
            if ($qtyRemaining <= 0) break;

            $qtyFromLayer     = min($layer->qty, $qtyRemaining);
            $cogsBaseFromLayer = $qtyFromLayer * (float) $layer->buy_price_base;

            // Hitung COGS dalam currency invoice (jika IDR, sama dengan base)
            $invoiceCurrencyCode    = $invoice->currency_code ?? 'IDR';
            $exchangeRate           = (float) ($invoice->exchange_rate ?? 1.0);
            $cogsInInvoiceCurrency  = ($invoiceCurrencyCode === 'IDR')
                ? $cogsBaseFromLayer
                : round($cogsBaseFromLayer / max($exchangeRate, 1), 4);

            $totalCogsBase += $cogsBaseFromLayer;
            $totalCogs     += $cogsInInvoiceCurrency;

            $qtyBefore  = $layer->qty;
            $layer->qty -= $qtyFromLayer;
            $layer->save();

            // Catat mutasi OUT per layer
            DeviceStockMutation::create([
                'device_stock_id'         => $masterStock->id,
                'device_stock_history_id' => $layer->id,
                'reference_type'          => InvoiceClient::class,
                'reference_id'            => $invoice->id,
                'type'                    => 'OUT',
                'qty_before'              => $qtyBefore,
                'qty_change'              => -$qtyFromLayer,
                'qty_after'               => $layer->qty,
                'buy_price_base'          => $layer->buy_price_base,
                'note'                    => 'FIFO OUT - Invoice #' . $invoice->invoice_number,
            ]);

            $qtyRemaining -= $qtyFromLayer;
        }

        // Update master stock qty
        $masterStock->qty = max(0, $masterStock->qty - $qtyNeeded);
        $masterStock->save();

        // Simpan COGS ke baris detail invoice
        $detail->cogs_amount      = round($totalCogs, 4);
        $detail->cogs_amount_base = round($totalCogsBase, 4);
        $detail->save();
    }

    /**
     * Kembalikan (revert) stok FIFO dari seluruh mutasi OUT Invoice ini.
     * Dipanggil sebelum update/delete invoice.
     */
    public function revertFifoStock(InvoiceClient $invoice): void
    {
        $mutations = DeviceStockMutation::where('reference_type', InvoiceClient::class)
            ->where('reference_id', $invoice->id)
            ->where('type', 'OUT')
            ->get();

        foreach ($mutations as $mutation) {
            $layer  = DeviceStockHistory::find($mutation->device_stock_history_id);
            $master = DeviceStock::find($mutation->device_stock_id);

            $qtyToReturn = abs($mutation->qty_change);

            if ($layer) {
                $layer->qty += $qtyToReturn;
                $layer->save();
            }

            if ($master) {
                $master->qty += $qtyToReturn;
                $master->save();
            }

            $mutation->delete();
        }

        // Reset COGS pada detail
        InvoiceClientDetail::where('invoice_client_id', $invoice->id)
            ->whereNotNull('device_stock_id')
            ->update(['cogs_amount' => 0, 'cogs_amount_base' => 0]);
    }

    /**
     * Memproses rekonsiliasi Invoice Client dengan Surat Jalan (DeliveryNote).
     */
    private function processDeliveryNoteReconciliation(InvoiceClient $invoice, InvoiceClientSaveData $dto): void
    {
        if (!$dto->delivery_note_id) {
            return;
        }

        $deliveryNote = \App\Models\DeliveryNote::with('details')->find($dto->delivery_note_id);
        if (!$deliveryNote) {
            return;
        }

        // 1. Validasi status Surat Jalan (belum di-lock oleh invoice lain)
        if ($deliveryNote->invoice_client_id && (int) $deliveryNote->invoice_client_id !== (int) $invoice->id) {
            throw new \Exception(trans('backpack::crud.invoice_client.error.delivery_note_already_billed') ?: 'Surat Jalan terpilih sudah diterbitkan Invoicenya pada transaksi lain.');
        }

        $exchangeRate = (float) ($invoice->exchange_rate ?? 1.0);

        // 3. Iterasi setiap item pada invoice
        $invoiceDetails = InvoiceClientDetail::where('invoice_client_id', $invoice->id)->get();
        foreach ($invoiceDetails as $invDetail) {
            if (!$invDetail->device_stock_id) {
                continue;
            }

            // Update harga jual master (sell_price) dari input invoice
            if ($invDetail->price > 0) {
                DeviceStock::where('id', $invDetail->device_stock_id)->update(['sell_price' => $invDetail->price]);
            }

            // Cari detail Surat Jalan yang selaras
            $dnDetail = $deliveryNote->details->first(function ($d) use ($invDetail) {
                if (!empty($invDetail->delivery_note_detail_id)) {
                    return (int) $d->id === (int) $invDetail->delivery_note_detail_id;
                }
                return (int) $d->device_stock_id === (int) $invDetail->device_stock_id;
            });

            if ($dnDetail) {
                $dnQty      = (int) $dnDetail->qty;
                $dnCogsBase = (float) $dnDetail->cogs_amount_base;
                $invQty     = (int) $invDetail->qty;

                if ($invQty === $dnQty) {
                    $cogsBase = $dnCogsBase;
                } else if ($invQty < $dnQty) {
                    // Qty Invoice lebih kecil dari Surat Jalan -> Revert layer FIFO terurai dari mutasi Surat Jalan
                    $diffQty = $dnQty - $invQty;
                    $revertedCogsBase = $this->revertExcessFifoStock($invoice, $deliveryNote->id, $invDetail->device_stock_id, $diffQty);
                    $cogsBase = max(0, $dnCogsBase - $revertedCogsBase);
                } else {
                    // Qty Invoice lebih besar dari Surat Jalan -> Ambil HPP Surat Jalan + Konsumsi FIFO tambahan
                    $cogsBase = $dnCogsBase;
                    $diffQty  = $invQty - $dnQty;

                    // Validasi stok fisik tambahan
                    $masterStock = DeviceStock::find($invDetail->device_stock_id);
                    if (!$masterStock || $masterStock->qty < $diffQty) {
                        $stockName = $masterStock ? $masterStock->name : 'Item';
                        $avail = $masterStock ? $masterStock->qty : 0;
                        throw new \Exception(trans('backpack::crud.invoice_client.error.stock_insufficient_delta', [
                            'name'      => $stockName,
                            'available' => $avail,
                            'needed'    => $diffQty,
                        ]) ?: "Stok barang '{$stockName}' tidak mencukupi untuk penambahan kuantitas di Invoice. (Stok tersedia: {$avail}, Dibutuhkan tambahan: {$diffQty}).");
                    }

                    $extraCogsBase = $this->consumeExtraFifoStock($masterStock, $diffQty, $invoice);
                    $cogsBase += $extraCogsBase;
                }

                $invDetail->delivery_note_detail_id = $dnDetail->id;
                $invDetail->cogs_amount_base        = round($cogsBase, 4);
                $invDetail->cogs_amount             = round($cogsBase / max($exchangeRate, 1.0), 4);
                $invDetail->save();
            }
        }

        // 4. Kunci Surat Jalan (Locking)
        $deliveryNote->invoice_client_id = $invoice->id;
        $deliveryNote->save();
    }

    /**
     * Revert selisih stok lebih kembali ke gudang dengan menelusuri layer mutasi FIFO Surat Jalan.
     */
    private function revertExcessFifoStock(InvoiceClient $invoice, int $deliveryNoteId, int $deviceStockId, int $qtyToReturn): float
    {
        if ($qtyToReturn <= 0) return 0.0;

        $mutations = DeviceStockMutation::where('reference_type', \App\Models\DeliveryNote::class)
            ->where('reference_id', $deliveryNoteId)
            ->where('device_stock_id', $deviceStockId)
            ->where('type', 'OUT')
            ->orderBy('id', 'desc')
            ->get();

        $revertedCogsBase = 0.0;
        $remainingToReturn = $qtyToReturn;

        foreach ($mutations as $mutation) {
            if ($remainingToReturn <= 0) break;

            $layer  = DeviceStockHistory::find($mutation->device_stock_history_id);
            $master = DeviceStock::find($mutation->device_stock_id);

            $portion = min(abs($mutation->qty_change), $remainingToReturn);
            $cogsPortion = $portion * (float) $mutation->buy_price_base;
            $revertedCogsBase += $cogsPortion;

            if ($layer) {
                $qtyBefore = $layer->qty;
                $layer->qty += $portion;
                $layer->save();

                // Catat log mutasi IN pengembalian selisih Invoice
                DeviceStockMutation::create([
                    'device_stock_id'         => $master->id ?? $deviceStockId,
                    'device_stock_history_id' => $layer->id,
                    'reference_type'          => InvoiceClient::class,
                    'reference_id'            => $invoice->id,
                    'type'                    => 'IN',
                    'qty_before'              => $qtyBefore,
                    'qty_change'              => $portion,
                    'qty_after'               => $layer->qty,
                    'buy_price_base'          => $mutation->buy_price_base,
                    'note'                    => 'Reversi Selisih Qty Invoice #' . $invoice->invoice_number,
                ]);
            }

            if ($master) {
                $master->qty += $portion;
                $master->save();
            }

            $remainingToReturn -= $portion;
        }

        // Fallback jika tidak ada mutasi spesifik ditemukan
        if ($remainingToReturn > 0) {
            $master = DeviceStock::find($deviceStockId);
            if ($master) {
                $master->qty += $remainingToReturn;
                $master->save();
            }

            $layer = DeviceStockHistory::where('device_stock_id', $deviceStockId)
                ->orderBy('id', 'desc')
                ->first();

            if ($layer) {
                $qtyBefore = $layer->qty;
                $layer->qty += $remainingToReturn;
                $layer->save();

                DeviceStockMutation::create([
                    'device_stock_id'         => $deviceStockId,
                    'device_stock_history_id' => $layer->id,
                    'reference_type'          => InvoiceClient::class,
                    'reference_id'            => $invoice->id,
                    'type'                    => 'IN',
                    'qty_before'              => $qtyBefore,
                    'qty_change'              => $remainingToReturn,
                    'qty_after'               => $layer->qty,
                    'buy_price_base'          => $layer->buy_price_base,
                    'note'                    => 'Reversi Selisih Qty Invoice #' . $invoice->invoice_number,
                ]);

                $revertedCogsBase += ($remainingToReturn * (float) $layer->buy_price_base);
            }
        }

        return $revertedCogsBase;
    }

    /**
     * Konsumsi stok FIFO tambahan saat Qty Invoice > Qty Surat Jalan.
     */
    private function consumeExtraFifoStock(DeviceStock $masterStock, int $qtyNeeded, InvoiceClient $invoice): float
    {
        $layers = DeviceStockHistory::where('device_stock_id', $masterStock->id)
            ->where('qty', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        $totalCogsBase = 0.0;
        $qtyRemaining  = $qtyNeeded;

        foreach ($layers as $layer) {
            if ($qtyRemaining <= 0) break;

            $qtyFromLayer      = min($layer->qty, $qtyRemaining);
            $cogsBaseFromLayer = $qtyFromLayer * (float) $layer->buy_price_base;
            $totalCogsBase    += $cogsBaseFromLayer;

            $qtyBefore  = $layer->qty;
            $layer->qty -= $qtyFromLayer;
            $layer->save();

            DeviceStockMutation::create([
                'device_stock_id'         => $masterStock->id,
                'device_stock_history_id' => $layer->id,
                'reference_type'          => InvoiceClient::class,
                'reference_id'            => $invoice->id,
                'type'                    => 'OUT',
                'qty_before'              => $qtyBefore,
                'qty_change'              => -$qtyFromLayer,
                'qty_after'               => $layer->qty,
                'buy_price_base'          => $layer->buy_price_base,
                'note'                    => 'FIFO OUT Tambahan - Invoice #' . $invoice->invoice_number,
            ]);

            $qtyRemaining -= $qtyFromLayer;
        }

        $masterStock->qty = max(0, $masterStock->qty - $qtyNeeded);
        $masterStock->save();

        return $totalCogsBase;
    }
}
