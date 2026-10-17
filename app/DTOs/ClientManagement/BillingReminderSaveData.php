<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class BillingReminderSaveData
{
    public function __construct(
        public ?int $company_id,
        public string $code_billing,
        public ?string $installation_date = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?string $period = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Normalize period value if needed
        $period = $request->input('period');
        if (!empty($period)) {
            $period = match (strtolower(trim($period))) {
                'sebulan', '1 bulan', '1_month', '1 month' => '1_month',
                '3 bulan', '3_months', '3 months' => '3_months',
                '6 bulan', '6_months', '6 months' => '6_months',
                'setahun', '1 tahun', '1_year', '1 year' => '1_year',
                default => $period,
            };
        }

        return new self(
            company_id: $request->input('company_id') ? (int) $request->input('company_id') : null,
            code_billing: trim((string) $request->input('code_billing', '')),
            installation_date: $request->input('installation_date') ?: null,
            start_date: $request->input('start_date') ?: null,
            end_date: $request->input('end_date') ?: null,
            period: $period ?: null,
        );
    }
}
