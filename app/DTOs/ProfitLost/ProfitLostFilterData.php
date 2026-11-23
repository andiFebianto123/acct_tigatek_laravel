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
        public readonly ?int $companyId = null,
        public readonly ?string $filter_paid_status = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $year = $request->get('filter_year');
        $category = $request->get('category');
        $type = $request->get('type');
        $filter_paid_status = $request->get('filter_paid_status');
        if ($filter_paid_status === 'all' || empty($filter_paid_status)) {
            $filter_paid_status = null;
        }
        $id = $request->get('id') ?? $request->get('_id');
        $companyId = $request->get('company_id') ?? $request->get('filter_company');
        if ($companyId === 'all' || empty($companyId)) {
            $companyId = null;
        }

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
            companyId: $companyId ? (int) $companyId : null,
            filter_paid_status: $filter_paid_status,
        );
    }
}
