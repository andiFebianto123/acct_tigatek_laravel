<?php

namespace App\DTOs\SubkonManagement;

use Illuminate\Http\Request;

class SpkFilterData
{
    public function __construct(
        public ?string $tab = 'all',
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

        return new self(
            tab: $request->input('tab', 'all'),
            year: $request->input('filter_year', 'all'),
            columnFilters: $columnFilters
        );
    }

    public function getColumnFilter(int $index): ?string
    {
        return $this->columnFilters[$index] ?? null;
    }
}
