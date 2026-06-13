<?php

namespace App\Imports;

use App\Models\BillingDevice;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BillingDeviceImport implements OnEachRow, WithHeadingRow
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

        // Skip if device_id is empty
        $deviceId = trim($data['device_id'] ?? '');
        if (empty($deviceId)) {
            return;
        }

        $subscriptionExpiryDate = $this->transformDate($data['subscription_expiry_date'] ?? null);
        $installationDate = $this->transformDate($data['installation_date'] ?? null);
        $expiredDate = $this->transformDate($data['expired_date'] ?? null);

        BillingDevice::updateOrCreate(
            [
                'device_id' => $deviceId,
                'company_id' => $this->companyId,
            ],
            [
                'phone' => isset($data['phone']) ? trim($data['phone']) : null,
                'vehicle_uid' => isset($data['vehicle_uid']) ? trim($data['vehicle_uid']) : null,
                'vehicle_name' => isset($data['vehicle_name']) ? trim($data['vehicle_name']) : null,
                'imei' => isset($data['imei']) ? trim($data['imei']) : null,
                'speed_limit' => isset($data['speed_limit']) ? (int) $data['speed_limit'] : null,
                'sim_network' => isset($data['sim_network']) ? trim($data['sim_network']) : null,
                'category' => isset($data['category']) ? trim($data['category']) : null,
                'model' => isset($data['model']) ? trim($data['model']) : null,
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
