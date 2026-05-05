<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class QuotationSelectionRequestData
{
    public function __construct(
        public ?int $company_id = null,
        public ?string $search = null,
        public ?int $order_column = null,
        public string $order_dir = 'asc',
        public int $start = 0,
        public int $length = 10,
        public ?int $draw = null,
        public array $ids = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            company_id: $request->input('company_id') ? (int) $request->input('company_id') : null,
            search: $request->input('search.value'),
            order_column: $request->input('order.0.column') !== null ? (int) $request->input('order.0.column') : null,
            order_dir: $request->input('order.0.dir', 'asc'),
            start: (int) $request->input('start', 0),
            length: (int) $request->input('length', 10),
            draw: $request->input('draw') ? (int) $request->input('draw') : null,
            ids: (array) $request->input('ids', []),
        );
    }
}
