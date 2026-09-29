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

        $clientId = null;
        $clientName = trim($data['client'] ?? ($data['nama_client'] ?? ($data['client_name'] ?? '')));
        if (!empty($clientName)) {
            $clientQuery = \App\Models\Client::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($clientName)]);
            if ($this->companyId) {
                $clientQuery->where(function ($q) {
                    $q->where('company_id', $this->companyId)
                      ->orWhereNull('company_id');
                });
            }
            $client = $clientQuery->first();

            if (!$client) {
                throw new \Exception("Klien '{$clientName}' tidak ditemukan atau tidak sesuai dengan perusahaan yang dipilih (Baris {$row->getIndex()}).");
            }
            $clientId = $client->id;
        }

        $subscriptionExpiryDate = $this->transformDate(
            $data['subscription_expiry_date'] ?? (
                $data['subscription_expired_date'] ?? (
                    $data['subscription_expiry'] ?? (
                        $data['tanggal_kedaluwarsa_langganan'] ?? (
                            $data['tgl_kedaluwarsa_langganan'] ?? ($data['tgl_expiry_langganan'] ?? null)
                        )
                    )
                )
            )
        );
        $installationDate = $this->transformDate(
            $data['installation_date'] ?? (
                $data['install_date'] ?? (
                    $data['tanggal_instalasi'] ?? (
                        $data['tgl_instalasi'] ?? (
                            $data['tanggal_pemasangan'] ?? ($data['tgl_pemasangan'] ?? null)
                        )
                    )
                )
            )
        );
        $expiredDate = $this->transformDate(
            $data['expired_date'] ?? (
                $data['expiry_date'] ?? (
                    $data['expire_date'] ?? (
                        $data['tanggal_expired'] ?? (
                            $data['tgl_expired'] ?? (
                                $data['tanggal_kedaluwarsa'] ?? ($data['tgl_kedaluwarsa'] ?? null)
                            )
                        )
                    )
                )
            )
        );

        BillingSimcard::updateOrCreate(
            [
                'iccid' => $iccid,
                'company_id' => $this->companyId,
            ],
            [
                'client_id' => $clientId,
                'code_billing' => isset($data['code_billing']) ? trim($data['code_billing']) : (isset($data['kode_billing']) ? trim($data['kode_billing']) : null),
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

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            // If it's a numeric value from Excel serial date
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            $value = trim((string) $value);
            if (empty($value) || in_array(strtolower($value), ['-', 'n/a', 'null', 'none'])) {
                return null;
            }

            // Standardize format DD/MM/YYYY, DD-MM-YYYY, or DD.MM.YYYY
            if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', $value, $matches)) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}")->format('Y-m-d');
            }

            // Standardize format YYYY-MM-DD, YYYY/MM/DD, or YYYY.MM.DD
            if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $value, $matches)) {
                $year = $matches[1];
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
                return Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}")->format('Y-m-d');
            }

            // Otherwise try parsing standard date string via Carbon
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            // Return null if date parsing fails to prevent crash
            return null;
        }
    }
}
