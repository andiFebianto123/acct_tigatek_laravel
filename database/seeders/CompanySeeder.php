<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Tigatek',
                'address' => 'Jl. Sudirman No. 123',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'postal_code' => '12190',
                'phone' => '021-5551234',
            ],
            [
                'name' => 'Duatek',
                'address' => 'Jl. Asia Afrika No. 45',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40111',
                'phone' => '022-4445678',
            ],
            [
                'name' => 'Innovatech',
                'address' => 'Jl. Pemuda No. 88',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'postal_code' => '60271',
                'phone' => '031-3339000',
            ],
        ];

        foreach ($companies as $companyData) {
            Company::updateOrCreate(
                ['name' => $companyData['name']],
                $companyData
            );
        }
    }
}
