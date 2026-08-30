<?php

namespace App\DTOs\CompanyManagement;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class CompanyData
{
    public function __construct(
        public ?string $name,
        public ?string $address,
        public ?string $city,
        public ?string $province,
        public ?string $postal_code = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $website = null,
        public UploadedFile|string|null $logo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            address: $request->input('address'),
            city: $request->input('city'),
            province: $request->input('province'),
            postal_code: $request->input('postal_code'),
            phone: $request->input('phone'),
            email: $request->input('email'),
            website: $request->input('website'),
            logo: $request->file('logo') ?? $request->input('logo'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
        ];
    }
}
