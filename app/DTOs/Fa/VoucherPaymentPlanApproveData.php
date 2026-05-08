<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherPaymentPlanApproveData
{
    public function __construct(
        public readonly ?string $no_apprv,
        public readonly string  $action,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            no_apprv: $request->input('no_apprv'),
            action:   $request->input('action', ''),
        );
    }
}
