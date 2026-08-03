<?php

namespace App\DTOs\ClientManagement;

class ClientQuotationDetailData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $device_stock_id,
        public readonly string $item_name,
        public readonly float $qty,
        public readonly ?string $unit,
        public readonly float $unit_price,
        public readonly float $total_price,
        public readonly float $unit_price_base,
        public readonly float $total_price_base,
    ) {}

    public static function fromArray(array $data, float $exchangeRate = 1.0): self
    {
        $qty = (float) str_replace(',', '', $data['qty'] ?? 1);
        $unit_price = (float) str_replace(',', '', $data['unit_price'] ?? $data['price'] ?? 0);
        $total_price = isset($data['total_price']) && $data['total_price'] !== ''
            ? (float) str_replace(',', '', $data['total_price'])
            : ($qty * $unit_price);

        $unit_price_base = isset($data['unit_price_base'])
            ? (float) str_replace(',', '', $data['unit_price_base'])
            : ($unit_price * $exchangeRate);

        $total_price_base = isset($data['total_price_base'])
            ? (float) str_replace(',', '', $data['total_price_base'])
            : ($total_price * $exchangeRate);

        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            device_stock_id: isset($data['device_stock_id']) && $data['device_stock_id'] !== '' ? (int) $data['device_stock_id'] : null,
            item_name: $data['item_name'] ?? $data['name'] ?? '',
            qty: $qty,
            unit: $data['unit'] ?? null,
            unit_price: $unit_price,
            total_price: $total_price,
            unit_price_base: $unit_price_base,
            total_price_base: $total_price_base,
        );
    }

    public function toArray(): array
    {
        return [
            'device_stock_id' => $this->device_stock_id,
            'item_name' => $this->item_name,
            'qty' => $this->qty,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'unit_price_base' => $this->unit_price_base,
            'total_price_base' => $this->total_price_base,
        ];
    }
}
