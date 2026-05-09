<?php

namespace App\DTOs\Coa;

use Illuminate\Http\Request;

class BalanceSheetFilterData
{
    public function __construct(
        public readonly ?string $type,
        public readonly ?string $year,
        public readonly ?int $quarter,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $type = $request->input('_type');
        $year = $request->input('filter_year') ?? $request->input('amp;filter_year') ?? date('Y');
        $quarter = $request->input('filter_quarter') ?? $request->input('amp;filter_quarter');
        
        $startDate = null;
        $endDate = null;

        if ($year && $year !== "all" && $year !== "") {
            $startDate = $year . '-01-01';
            $endDate = $year . '-12-31';

            if ($quarter) {
                $quartersRanges = [
                    1 => ['start' => $year . '-01-01', 'end' => $year . '-03-31'],
                    2 => ['start' => $year . '-04-01', 'end' => $year . '-06-30'],
                    3 => ['start' => $year . '-07-01', 'end' => $year . '-09-30'],
                    4 => ['start' => $year . '-10-01', 'end' => $year . '-12-31'],
                ];
                if (isset($quartersRanges[$quarter])) {
                    $startDate = $quartersRanges[$quarter]['start'];
                    $endDate = $quartersRanges[$quarter]['end'];
                }
            }
        }

        return new self(
            type: $type,
            year: $year,
            quarter: $quarter ? (int) $quarter : null,
            startDate: $startDate,
            endDate: $endDate,
        );
    }
}
