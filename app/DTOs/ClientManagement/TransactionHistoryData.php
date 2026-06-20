<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class TransactionHistoryData
{
    public function __construct(
        public ?string $transaction_id,
        public ?string $device_id,
        public ?string $msisdn,
        public ?string $op_completion_time,
        public ?string $operations,
        public ?int $devices_upload,
        public ?int $device_prosses,
        public ?int $device_update,
        public ?string $last_update,
        public ?string $status,
        public ?int $company_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $company_id = null;
        if (backpack_user() && backpack_user()->hasRole('Super Admin')) {
            $company_id = $request->input('company_id') ? (int) $request->input('company_id') : null;
        } else if (backpack_user()) {
            $company_id = backpack_user()->company_id ? (int) backpack_user()->company_id : null;
        }

        return new self(
            transaction_id: $request->input('transaction_id'),
            device_id: $request->input('device_id'),
            msisdn: $request->input('msisdn'),
            op_completion_time: self::parseDateTime($request->input('op_completion_time')),
            operations: $request->input('operations'),
            devices_upload: $request->has('devices_upload') && $request->input('devices_upload') !== null ? (int) $request->input('devices_upload') : null,
            device_prosses: $request->has('device_prosses') && $request->input('device_prosses') !== null ? (int) $request->input('device_prosses') : null,
            device_update: $request->has('device_update') && $request->input('device_update') !== null ? (int) $request->input('device_update') : null,
            last_update: self::parseDateTime($request->input('last_update')),
            status: $request->input('status'),
            company_id: $company_id,
        );
    }

    private static function parseDateTime(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $value = trim($value);
        $formats = [
            'd/m/Y H:i:s', 'm/d/Y H:i:s', 'Y-m-d H:i:s', 'd-m-Y H:i:s', 'Y/m/d H:i:s',
            'd/m/Y H:i', 'm/d/Y H:i', 'Y-m-d H:i', 'd-m-Y H:i', 'Y/m/d H:i',
            'Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'
        ];
        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                continue;
            }
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'device_id' => $this->device_id,
            'msisdn' => $this->msisdn,
            'op_completion_time' => $this->op_completion_time,
            'operations' => $this->operations,
            'devices_upload' => $this->devices_upload,
            'device_prosses' => $this->device_prosses,
            'device_update' => $this->device_update,
            'last_update' => $this->last_update,
            'status' => $this->status,
            'company_id' => $this->company_id,
        ];
    }
}
