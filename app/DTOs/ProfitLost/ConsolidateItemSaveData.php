<?php

namespace App\DTOs\ProfitLost;

use Illuminate\Http\Request;

class ConsolidateItemSaveData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $header_id,
        public readonly int $account_id,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id: $request->id ? (int) $request->id : null,
            header_id: (int) $request->header_id,
            account_id: (int) $request->account_id,
        );
    }

    public function toArray(): array
    {
        return [
            'header_id' => $this->header_id,
            'account_id' => $this->account_id,
        ];
    }
}
