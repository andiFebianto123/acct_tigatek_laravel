<?php

namespace App\DTOs\Coa;

use Illuminate\Http\Request;

class CoaFilterData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $year,
        public readonly ?string $quarter,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $year = $request->filter_year ?? $request->input('amp;filter_year') ?? date('Y');
        $quarter = $request->filter_quarter ?? $request->input('amp;filter_quarter');
        
        $startDate = null;
        $endDate = null;

        if ($year && $year != "" && $year != "all") {
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
            id: $request->input('_id') ? (int) $request->input('_id') : null,
            year: $year,
            quarter: $quarter,
            startDate: $startDate,
            endDate: $endDate,
        );
    }
}
