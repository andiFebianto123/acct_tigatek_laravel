<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class BillingDeviceData
{
    public function __construct(
        public ?int $company_id,
        public string $device_id,
        public ?string $phone,
        public ?string $vehicle_uid,
        public ?string $vehicle_name,
        public ?string $imei,
        public ?int $speed_limit,
        public ?string $sim_network,
        public ?string $category,
        public ?string $model,
        public ?string $subscription_expiry_date,
        public ?string $installation_date,
        public ?string $expired_date,
        public ?string $reminder_date,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            company_id: $request->input('company_id') ? (int) $request->input('company_id') : null,
            device_id: $request->input('device_id'),
            phone: $request->input('phone'),
            vehicle_uid: $request->input('vehicle_uid'),
            vehicle_name: $request->input('vehicle_name'),
            imei: $request->input('imei'),
            speed_limit: $request->input('speed_limit') !== null ? (int) $request->input('speed_limit') : null,
            sim_network: $request->input('sim_network'),
            category: $request->input('category'),
            model: $request->input('model'),
            subscription_expiry_date: $request->input('subscription_expiry_date'),
            installation_date: $request->input('installation_date'),
            expired_date: $request->input('expired_date'),
            reminder_date: $request->input('reminder_date'),
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'device_id' => $this->device_id,
            'phone' => $this->phone,
            'vehicle_uid' => $this->vehicle_uid,
            'vehicle_name' => $this->vehicle_name,
            'imei' => $this->imei,
            'speed_limit' => $this->speed_limit,
            'sim_network' => $this->sim_network,
            'category' => $this->category,
            'model' => $this->model,
            'subscription_expiry_date' => $this->subscription_expiry_date,
            'installation_date' => $this->installation_date,
            'expired_date' => $this->expired_date,
            'reminder_date' => $this->reminder_date,
        ];
    }
}
