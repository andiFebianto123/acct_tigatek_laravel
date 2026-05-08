<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherPaymentPlanFilterData
{
    public function __construct(
        public readonly ?string $filter_year,
        public readonly ?string $tab,
        public readonly ?string $type,
        public readonly mixed   $columns,
        public readonly mixed   $search,
        public readonly mixed   $order,
        public readonly int     $start,
        public readonly int     $length,
        public readonly int     $draw,
        public readonly mixed   $searchAll,
        public readonly mixed   $searchNonRutin,
        public readonly mixed   $searchSubkon,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            filter_year:    $request->input('filter_year'),
            tab:            $request->input('tab'),
            type:           $request->input('type'),
            columns:        $request->input('columns'),
            search:         $request->input('search'),
            order:          $request->input('order'),
            start:          (int) $request->input('start', 0),
            length:         (int) $request->input('length', 10),
            draw:           (int) $request->input('draw', 1),
            searchAll:      $request->input('searchAll', []),
            searchNonRutin: $request->input('searchNonRutin', []),
            searchSubkon:   $request->input('searchSubkon', []),
        );
    }

    /**
     * Get value of a datatable column filter by index.
     */
    public function getColumnFilter(int $index): ?string
    {
        $value = $this->columns[$index]['search']['value'] ?? '';
        return trim($value) !== '' ? trim($value) : null;
    }
}
