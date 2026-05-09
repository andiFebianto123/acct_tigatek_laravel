<?php

namespace App\DTOs\Coa;

use Illuminate\Http\Request;

class CoaSaveData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?float $balance,
        public readonly ?string $type = null,
        public readonly ?int $level = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $cleanNominal = fn($val) => (float) str_replace('.', '', $val ?? '0');

        return new self(
            id: $request->id ? (int) $request->id : null,
            code: $request->code,
            name: $request->name,
            balance: $cleanNominal($request->balance),
            type: $request->type,
            level: $request->level ? (int) $request->level : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'balance' => $this->balance,
            'type' => $this->type,
            'level' => $this->level,
        ];
    }
}
