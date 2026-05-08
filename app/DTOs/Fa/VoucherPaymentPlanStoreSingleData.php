<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherPaymentPlanStoreSingleData
{
    public function __construct(
        public readonly int     $id,
        public readonly ?string $date,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id:   (int) $request->input('id'),
            date: $request->input('date'),
        );
    }
}
