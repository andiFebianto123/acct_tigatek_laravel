<?php

namespace App\DTOs\Asset;

use Illuminate\Http\Request;

class AssetSaveData
{
    public function __construct(
        public ?int $id,
        public int $account_id,
        public int $depreciation_account_id,
        public int $expense_account_id,
        public string $description,
        public string $year_acquisition,
        public float $price_acquisition,
        public float $economic_age,
        public ?float $price_rate_year_ago,
        public ?float $accumulated_until_december_last_year,
        public ?float $book_value_last_december,
        public ?int $company_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id: $request->id ? (int) $request->id : null,
            account_id: (int) $request->account_id,
            depreciation_account_id: (int) $request->depreciation_account_id,
            expense_account_id: (int) $request->expense_account_id,
            description: $request->description,
            year_acquisition: $request->year_acquisition,
            price_acquisition: (float) str_replace('.', '', $request->price_acquisition),
            economic_age: (float) $request->economic_age,
            price_rate_year_ago: $request->price_rate_year_ago ? (float) str_replace('.', '', $request->price_rate_year_ago) : 0,
            accumulated_until_december_last_year: $request->accumulated_until_december_last_year ? (float) str_replace('.', '', $request->accumulated_until_december_last_year) : 0,
            book_value_last_december: $request->book_value_last_december ? (float) str_replace('.', '', $request->book_value_last_december) : 0,
            company_id: $request->company_id ? (int) $request->company_id : null,
        );
    }
}
