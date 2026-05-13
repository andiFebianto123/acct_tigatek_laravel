<?php

namespace App\DTOs\CastAccount;

use Illuminate\Http\Request;

class CastAccountFilterData
{
    public function __construct(
        public readonly ?string $year,
        public readonly ?string $order,
        public readonly ?string $search = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            year: $request->filter_year === 'all' ? null : $request->filter_year,
            order: $request->filter_cash_account_order ?? 'ASC',
            search: $request->search ? $request->search['value'] : null
        );
    }
}
