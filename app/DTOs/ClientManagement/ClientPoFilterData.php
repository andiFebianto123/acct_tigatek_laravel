<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class ClientPoFilterData
{
    public function __construct(
        public ?string $status_invoice = null,
        public ?string $date_po = null,
        public ?string $year = 'all',
        public array $columnFilters = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $columnFilters = [];
        
        // Handle DataTables columns search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                $searchValue = trim($column['search']['value'] ?? '');
                if ($searchValue !== '') {
                    $columnFilters[$index] = $searchValue;
                }
            }
        }
        
        // Handle custom search plugin if exists
        if ($request->has('search') && is_array($request->search)) {
            foreach ($request->search as $index => $searchValue) {
                $searchValue = trim($searchValue ?? '');
                if ($searchValue !== '') {
                    $columnFilters[$index] = $searchValue;
                }
            }
        }

        return new self(
            status_invoice: $request->input('status_invoice'),
            date_po: $request->input('date_po'),
            year: $request->input('filter_year', 'all'),
            columnFilters: $columnFilters
        );
    }

    public function getColumnFilter(int $index): ?string
    {
        return $this->columnFilters[$index] ?? null;
    }
}
