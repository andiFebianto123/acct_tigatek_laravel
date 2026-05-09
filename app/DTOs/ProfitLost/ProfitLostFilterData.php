<?php

namespace App\DTOs\ProfitLost;

use Illuminate\Http\Request;

class ProfitLostFilterData
{
    public function __construct(
        public readonly ?string $year,
        public readonly ?string $category,
        public readonly ?string $type,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly ?int $id,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $year = $request->get('filter_year');
        $category = $request->get('category');
        $type = $request->get('type');
        $id = $request->get('id') ?? $request->get('_id');

        $startDate = null;
        $endDate = null;

        if ($year && $year != 'all') {
            $startDate = $year . '-01-01 00:00:00';
            $endDate = $year . '-12-31 23:59:59';
        }

        return new self(
            year: $year,
            category: $category,
            type: $type,
            startDate: $startDate,
            endDate: $endDate,
            id: $id ? (int) $id : null,
        );
    }
}
