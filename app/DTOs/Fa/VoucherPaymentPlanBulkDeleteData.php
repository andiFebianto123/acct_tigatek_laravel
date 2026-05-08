<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherPaymentPlanBulkDeleteData
{
    public function __construct(
        public readonly array $entries,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $entries = json_decode($request->input('entries', '[]'), true);

        return new self(
            entries: is_array($entries) ? $entries : [],
        );
    }
}
