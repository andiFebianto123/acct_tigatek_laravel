<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherPaymentFilterData
{
    public function __construct(
        public readonly mixed $non_rutin,
        public readonly mixed $rutin,
        public readonly mixed $filter_year,
        public readonly mixed $tab,
        public readonly mixed $type,
        public readonly mixed $columns,
        public readonly mixed $search,
        public readonly int $start,
        public readonly int $length,
        public readonly mixed $order,
        public readonly int $draw,
        public readonly mixed $searchRutin,
        public readonly mixed $searchNonRutin,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->input('non_rutin'),
            $request->input('rutin'),
            $request->input('filter_year'),
            $request->input('tab'),
            $request->input('type'),
            $request->input('columns'),
            $request->input('search'),
            (int) $request->input('start', 0),
            (int) $request->input('length', 10),
            $request->input('order'),
            (int) $request->input('draw', 1),
            $request->input('searchRutin'),
            $request->input('searchNonRutin'),
        );
    }

    public function getColumnFilter(int $index, $key = 'columns'): ?string
    {
        return trim($this->{$key}[$index]['search']['value'] ?? '');
    }
}
