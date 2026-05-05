<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class ClientFilterData
{
    public function __construct(
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
            year: $request->input('filter_year', 'all'),
            columnFilters: $columnFilters
        );
    }

    public function getColumnFilter(int $index): ?string
    {
        return $this->columnFilters[$index] ?? null;
    }
}
