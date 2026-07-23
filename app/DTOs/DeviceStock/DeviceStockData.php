<?php

namespace App\DTOs\DeviceStock;

use Illuminate\Http\Request;

class DeviceStockData
{
    public function __construct(
        public ?string $name,
        public ?string $code,
        public ?int $category_id,
        public ?int $qty,
        public float $sell_price,
        public float $buy_price,
        public ?string $currency_code = 'IDR',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            code: $request->input('code'),
            category_id: $request->input('category_id') ? (int) $request->input('category_id') : null,
            qty: $request->input('qty') !== null ? (int) $request->input('qty') : 0,
            sell_price: (float) str_replace(',', '', $request->input('sell_price') ?? 0),
            buy_price: (float) str_replace(',', '', $request->input('buy_price') ?? 0),
            currency_code: $request->input('currency_code', 'IDR'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'category_id' => $this->category_id,
            'qty' => $this->qty,
            'sell_price' => $this->sell_price,
            'buy_price' => $this->buy_price,
            'currency_code' => $this->currency_code,
        ];
    }
}
