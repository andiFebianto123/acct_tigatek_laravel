<?php

namespace App\DTOs\ClientManagement;

use Illuminate\Http\Request;

class BillingSimcardData
{
    public function __construct(
        public ?int $company_id,
        public ?string $product,
        public ?string $device_name,
        public ?string $technology,
        public ?string $device_profile_id,
        public ?string $iccid,
        public ?string $msisdn,
        public ?string $status,
        public ?string $rate_plan,
        public ?string $subscription_expiry_date,
        public ?string $installation_date,
        public ?string $expired_date,
        public ?string $reminder_date,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            company_id: $request->input('company_id') ? (int) $request->input('company_id') : null,
            product: $request->input('product'),
            device_name: $request->input('device_name'),
            technology: $request->input('technology'),
            device_profile_id: $request->input('device_profile_id'),
            iccid: $request->input('iccid'),
            msisdn: $request->input('msisdn'),
            status: $request->input('status'),
            rate_plan: $request->input('rate_plan'),
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
            'product' => $this->product,
            'device_name' => $this->device_name,
            'technology' => $this->technology,
            'device_profile_id' => $this->device_profile_id,
            'iccid' => $this->iccid,
            'msisdn' => $this->msisdn,
            'status' => $this->status,
            'rate_plan' => $this->rate_plan,
            'subscription_expiry_date' => $this->subscription_expiry_date,
            'installation_date' => $this->installation_date,
            'expired_date' => $this->expired_date,
            'reminder_date' => $this->reminder_date,
        ];
    }
}
