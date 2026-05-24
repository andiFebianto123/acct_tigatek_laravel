<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class BastFilterData
{
    public function __construct(
        public ?string $date = null,
        public ?string $year = 'all',
        public ?int $company_id = null,
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

        $company_id = null;
        if (backpack_user() && backpack_user()->hasRole('Super Admin')) {
            $company_id = $request->input('company_id') ? (int) $request->input('company_id') : null;
        } else if (backpack_user()) {
            $company_id = backpack_user()->company_id ? (int) backpack_user()->company_id : null;
        }

        return new self(
            date: $request->input('date'),
            year: $request->input('filter_year', 'all'),
            company_id: $company_id,
            columnFilters: $columnFilters
        );
    }

    public function getColumnFilter(int $index): ?string
    {
        return $this->columnFilters[$index] ?? null;
    }
}
