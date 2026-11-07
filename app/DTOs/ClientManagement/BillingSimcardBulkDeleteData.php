<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class BillingSimcardBulkDeleteData
{
    /**
     * @param int[] $entries
     */
    public function __construct(
        public readonly array $entries,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = $request->input('entries', '[]');
        $entries = is_array($raw) ? $raw : json_decode($raw, true);

        return new self(
            entries: is_array($entries) ? array_values(array_filter(array_map('intval', $entries))) : [],
        );
    }
}

