<?php

namespace App\Imports;

use App\Models\TransactionHistory;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TransactionHistoryImport implements OnEachRow, WithHeadingRow
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

        // Skip if transaction_id is empty
        $transactionId = trim($data['transaction_id'] ?? '');
        if (empty($transactionId)) {
            return;
        }

        $opCompletionTime = $this->transformDateTime($data['op_completion_time'] ?? null);
        $lastUpdate = $this->transformDateTime($data['last_update'] ?? null);

        // Map Oprations or Operations
        $operations = null;
        if (isset($data['oprations'])) {
            $operations = trim($data['oprations']);
        } elseif (isset($data['operations'])) {
            $operations = trim($data['operations']);
        }

        TransactionHistory::updateOrCreate(
            [
                'transaction_id' => $transactionId,
                'company_id' => $this->companyId,
            ],
            [
                'device_id' => isset($data['device_id']) ? trim($data['device_id']) : null,
                'msisdn' => isset($data['msisdn']) ? trim($data['msisdn']) : null,
                'op_completion_time' => $opCompletionTime,
                'operations' => $operations,
                'devices_upload' => isset($data['devices_upload']) && is_numeric($data['devices_upload']) ? (int) $data['devices_upload'] : null,
                'device_prosses' => isset($data['device_prosses']) && is_numeric($data['device_prosses']) ? (int) $data['device_prosses'] : null,
                'device_update' => isset($data['device_update']) && is_numeric($data['device_update']) ? (int) $data['device_update'] : null,
                'last_update' => $lastUpdate,
                'status' => isset($data['status']) ? trim($data['status']) : null,
            ]
        );
    }

    /**
     * Transform Excel date/string to Y-m-d H:i:s format.
     */
    private function transformDateTime($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // If it's a numeric value from Excel serial date
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
            }

            $value = trim($value);

            // Try common date/time formats
            $formats = [
                'd/m/Y H:i:s', 'm/d/Y H:i:s', 'Y-m-d H:i:s', 'd-m-Y H:i:s', 'Y/m/d H:i:s',
                'd/m/Y H:i', 'm/d/Y H:i', 'Y-m-d H:i', 'd-m-Y H:i', 'Y/m/d H:i',
                'Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'
            ];
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    continue;
                }
            }

            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
