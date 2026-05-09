<?php

namespace App\DTOs\ProfitLost;

use Illuminate\Http\Request;

class ProjectProfitLostSaveData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $voucher_id,
        public readonly int $client_po_id,
        public readonly float $price_after_year,
        public readonly float $price_voucher,
        public readonly float $price_small_cash,
        public readonly float $price_total,
        public readonly float $price_profit_lost_po,
        public readonly float $price_general,
        public readonly float $price_prift_lost_final,
        public readonly string $category,
        public readonly ?int $company_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $cleanPrice = function($value) {
            if (is_null($value) || $value === '') return 0.0;
            if (is_numeric($value)) return (float) $value;
            // Menghapus titik (ribuan) dan mengganti koma dengan titik (desimal) jika ada
            $cleaned = str_replace('.', '', $value);
            $cleaned = str_replace(',', '.', $cleaned);
            return (float) $cleaned;
        };

        return new self(
            id: $request->id ? (int) $request->id : null,
            voucher_id: $request->voucher_id ? (int) $request->voucher_id : null,
            client_po_id: (int) ($request->work_code ?? $request->id_client_po ?? $request->client_po_id),
            price_after_year: $cleanPrice($request->price_after_year),
            price_voucher: $cleanPrice($request->price_voucher),
            price_small_cash: $cleanPrice($request->price_small_cash),
            price_total: $cleanPrice($request->price_total),
            price_profit_lost_po: $cleanPrice($request->price_profit_lost_po),
            price_general: $cleanPrice($request->price_general),
            price_prift_lost_final: $cleanPrice($request->price_prift_lost_final),
            category: $request->category ?? '',
            company_id: $request->company_id ? (int) $request->company_id : null,
        );
    }

    public function toArray(): array
    {
        return [
            'voucher_id' => $this->voucher_id,
            'client_po_id' => $this->client_po_id,
            'price_after_year' => $this->price_after_year,
            'price_voucher' => $this->price_voucher,
            'price_small_cash' => $this->price_small_cash,
            'price_total' => $this->price_total,
            'price_profit_lost_po' => $this->price_profit_lost_po,
            'price_general' => $this->price_general,
            'price_prift_lost_final' => $this->price_prift_lost_final,
            'category' => $this->category,
            'company_id' => $this->company_id,
        ];
    }
}
