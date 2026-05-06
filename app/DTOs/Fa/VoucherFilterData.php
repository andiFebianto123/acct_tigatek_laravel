<?php

namespace App\DTOs\Fa;

use Illuminate\Http\Request;

class VoucherFilterData
{
    public function __construct(
        public ?string $date_voucher = null,
        public ?string $bill_date = null,
        public ?string $payment_date = null,
        public ?string $year = 'all',
        public array $columnFilters = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $columnFilters = [];

        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                $searchValue = trim($column['search']['value'] ?? '');
                if ($searchValue !== '') {
                    $columnFilters[$index] = $searchValue;
                }
            }
        }

        if ($request->has('search') && is_array($request->search)) {
            foreach ($request->search as $index => $searchValue) {
                $searchValue = trim($searchValue ?? '');
                if ($searchValue !== '') {
                    $columnFilters[$index] = $searchValue;
                }
            }
        }

        return new self(
            date_voucher: $request->input('date_voucher'),
            bill_date: $request->input('bill_date'),
            payment_date: $request->input('payment_date'),
            year: $request->input('filter_year', 'all'),
            columnFilters: $columnFilters
        );
    }

    public function getColumnFilter(int $index): ?string
    {
        return $this->columnFilters[$index] ?? null;
    }
}
