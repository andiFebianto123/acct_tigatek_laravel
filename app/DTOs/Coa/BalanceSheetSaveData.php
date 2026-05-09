<?php

namespace App\DTOs\Coa;

use Illuminate\Http\Request;

class BalanceSheetSaveData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly ?float $balance,
        public readonly ?string $date,
        public readonly ?int $level = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id: $request->id ? (int) $request->id : null,
            code: $request->code,
            name: $request->name,
            type: $request->type,
            balance: $request->has('balance') ? (float) $request->balance : null,
            date: $request->date,
            level: $request->level ? (int) $request->level : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'balance' => $this->balance,
            'date' => $this->date,
            'level' => $this->level,
        ];
    }
}
