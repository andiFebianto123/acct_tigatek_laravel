<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\Client;
use App\Models\ClientPo;
use App\Models\Company;
use App\Models\InvoiceClient;

class InvoiceClientUmumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get first company (if exists)
        $company = Company::where('name', 'Tigatek')->first();

        // 2. Find or create Client "UMUM"
        $client = Client::firstOrCreate(
            ['name' => 'UMUM'],
            [
                'address' => 'Jl. Umum No. 1',
                'npwp' => '00.000.000.0-000.000',
                'phone' => '',
                'company_id' => $company?->id,
            ]
        );

        $clientPo = ClientPo::firstOrCreate(
            ['work_code' => 'UMUM-001'],
            [
                'client_id' => $client->id,
                'company_id' => $company?->id,
                'po_number' => 'PO-UMUM-001',
                'job_name' => 'Pekerjaan Umum',
                'job_value' => 0.00,
                'rap_value' => 0.00,
                'tax_ppn' => 0.00,
                'job_value_include_ppn' => 0.00,
                'price_after_year' => 0.00,
                'load_general_value' => 0.00,
                'profit_and_lost_final' => 0.00,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addYear()->toDateString(),
                'reimburse_type' => 'NON REIMBURSE',
                'price_total' => 0.00,
                'profit_and_loss' => 0.00,
                'category' => 'UMUM',
            ]
        );

        // 4. Find or create InvoiceClient for "UMUM"
        InvoiceClient::firstOrCreate(
            ['invoice_number' => 'INV-UMUM-001'],
            [
                'name' => 'Invoice Umum',
                'address_po' => 'Jl. Umum No. 1',
                'invoice_date' => Carbon::now()->toDateString(),
                'client_po_id' => $clientPo->id,
                'po_date' => Carbon::now()->toDateString(),
                'client_id' => $client->id,
                'price_total_exclude_ppn' => 0.00,
                'price_total_include_ppn' => 0.00,
                'price_total' => 0.00,
                'status' => 'Unpaid',
                'company_id' => $company?->id,
            ]
        );
    }
}
