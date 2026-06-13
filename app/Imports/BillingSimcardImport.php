<?php

namespace App\Imports;

use App\Models\BillingSimcard;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BillingSimcardImport implements OnEachRow, WithHeadingRow
{
    protected ?int $companyId;

    public function __construct(?int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Process each row from Excel.
     */
    public function onRow(Row $row)
    {
        $data = $row->toArray();

        // Skip if iccid is empty
        $iccid = trim($data['iccid'] ?? '');
        if (empty($iccid)) {
            return;
        }

        $subscriptionExpiryDate = $this->transformDate($data['subscription_expiry_date'] ?? null);
        $installationDate = $this->transformDate($data['installation_date'] ?? null);
        $expiredDate = $this->transformDate($data['expired_date'] ?? null);

        BillingSimcard::updateOrCreate(
            [
                'iccid' => $iccid,
                'company_id' => $this->companyId,
            ],
            [
                'product' => isset($data['product']) ? trim($data['product']) : null,
                'device_name' => isset($data['device_name']) ? trim($data['device_name']) : null,
                'technology' => isset($data['technology']) ? trim($data['technology']) : null,
                'device_profile_id' => isset($data['device_profile_id']) ? trim($data['device_profile_id']) : null,
                'msisdn' => isset($data['msisdn']) ? trim($data['msisdn']) : null,
                'status' => isset($data['status']) ? trim($data['status']) : null,
                'rate_plan' => isset($data['rate_plan']) ? trim($data['rate_plan']) : null,
                'subscription_expiry_date' => $subscriptionExpiryDate,
                'installation_date' => $installationDate,
                'expired_date' => $expiredDate,
            ]
        );
    }

    /**
     * Transform Excel date/string to Y-m-d format.
     */
    private function transformDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // If it's a numeric value from Excel serial date
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            // Otherwise try parsing standard date string via Carbon
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            // Return null if date parsing fails to prevent crash
            return null;
        }
    }
}
