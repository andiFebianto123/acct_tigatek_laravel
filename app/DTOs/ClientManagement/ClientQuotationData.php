<?php

namespace App\DTOs\ClientManagement;

use App\Models\DeviceStock;
use App\Models\Setting;
use Illuminate\Http\Request;

class ClientQuotationData
{
    /**
     * @param ClientQuotationDetailData[] $items
     */
    public function __construct(
        public readonly ?int $company_id,
        public readonly ?string $work_code,
        public readonly ?int $client_id,
        public readonly ?string $reimburse_type,
        public readonly ?string $po_number,
        public readonly ?string $job_name,
        public readonly ?string $currency_code,
        public readonly float $rap_value,
        public readonly float $job_value,
        public readonly float $tax_ppn,
        public readonly float $job_value_include_ppn,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly ?string $date_po,
        public readonly mixed $document_path,
        public readonly ?string $category,
        public readonly ?string $status,
        public readonly ?string $pic = null,
        public readonly array $items = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $currencyCode = $request->currency_code ?? 'IDR';
        $exchangeRate = 1.0;
        if ($currencyCode === 'USD') {
            $settings = Setting::first();
            $exchangeRate = (float) ($settings?->usd_rate ?? 16000);
        }

        $items = [];
        $rawDetails = $request->client_quotation_details ?? $request->client_quotation_details_edit ?? $request->items ?? [];
        if (is_string($rawDetails)) {
            $rawDetails = json_decode($rawDetails, true) ?? [];
        }
        if (is_array($rawDetails)) {
            foreach ($rawDetails as $item) {
                if (is_array($item)) {
                    $deviceStockId = isset($item['device_stock_id']) && $item['device_stock_id'] !== '' ? (int) $item['device_stock_id'] : null;
                    $name = $item['item_name'] ?? $item['name'] ?? '';
                    if (empty($name) && $deviceStockId) {
                        $device = DeviceStock::find($deviceStockId);
                        if ($device) {
                            $name = $device->name;
                        }
                    }

                    if (!empty($name) || $deviceStockId) {
                        $normalizedItem = [
                            'device_stock_id' => $deviceStockId,
                            'item_name' => $name,
                            'qty' => $item['qty'] ?? 1,
                            'unit' => $item['unit'] ?? null,
                            'unit_price' => $item['unit_price'] ?? $item['price'] ?? 0,
                        ];
                        $items[] = ClientQuotationDetailData::fromArray($normalizedItem, $exchangeRate);
                    }
                }
            }
        }

        return new self(
            company_id: $request->company_id ? (int) $request->company_id : null,
            work_code: $request->work_code,
            client_id: $request->client_id ? (int) $request->client_id : null,
            reimburse_type: $request->reimburse_type,
            po_number: $request->po_number,
            job_name: $request->job_name,
            currency_code: $currencyCode,
            rap_value: (float) str_replace(',', '', $request->rap_value ?? 0),
            job_value: (float) str_replace(',', '', $request->job_value ?? 0),
            tax_ppn: (float) ($request->tax_ppn ?? 0),
            job_value_include_ppn: (float) str_replace(',', '', $request->job_value_include_ppn ?? 0),
            start_date: $request->start_date,
            end_date: $request->end_date,
            date_po: $request->date_po,
            document_path: $request->file('document_path') ?? $request->document_path,
            category: $request->category,
            status: $request->status,
            pic: $request->pic ?? null,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'work_code' => $this->work_code,
            'client_id' => $this->client_id,
            'reimburse_type' => $this->reimburse_type,
            'po_number' => $this->po_number,
            'job_name' => $this->job_name,
            'currency_code' => $this->currency_code,
            'rap_value' => $this->rap_value,
            'job_value' => $this->job_value,
            'tax_ppn' => $this->tax_ppn,
            'job_value_include_ppn' => $this->job_value_include_ppn,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'date_po' => $this->date_po,
            'document_path' => $this->document_path,
            'category' => $this->category,
            'status' => $this->status,
            'pic' => $this->pic,
        ];
    }
}
