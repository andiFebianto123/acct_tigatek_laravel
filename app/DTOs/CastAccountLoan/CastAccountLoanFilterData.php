<?php

namespace App\DTOs\CastAccountLoan;

use Illuminate\Http\Request;

class CastAccountLoanFilterData
{
    public function __construct(
        public readonly ?string $year = null,
        public readonly ?string $order = 'ASC'
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            year: $request->filter_year,
            order: $request->order
        );
    }
}
