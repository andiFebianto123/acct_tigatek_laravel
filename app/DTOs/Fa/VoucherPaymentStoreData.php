<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherPaymentStoreData
{
    public function __construct(
        public readonly array $voucher
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->input('voucher', [])
        );
    }
}
