<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\AdditionalInformation;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdditionalInformation::class,
            BankSeeder::class,
            ConsolidateIncomeHeader::class,
            MenuPermissionSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SettingSeeder::class,
            UserSeeder::class,
            VoidPermissionSeeder::class,
            CompanySeeder::class,
        ]);
    }
}
