<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;


class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'APPROVE VOUCHER',
            'APPROVE EDIT VOUCHER',
            'APPROVE RENCANA BAYAR',
            'AKSES SEMUA VIEW ACCOUNTING',
            'AKSES SEMUA VIEW PROJECT',
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES SEMUA MENU PROJECT',
            'AKSES MENU VENDOR',
            'AKSES MENU CLIENT',
            'AKSES MENU FA',
            'AKSES SEMUA MENU RENCANA PEMBAYARAN',
            'AKSES SEMUA STATUS PENAWARAN PROJECT',
            'AKSES SEMUA DAFTAR PENAWARAN PROJECT',
            'AKSES SEMUA DATA PROYEKSI PEKERJAAN PROJECT',
            'EDIT KOLOM PROGRES DAN KETERANGAN DAFTAR PROJECT',
            'BUKA SEMUA PILIHAN PERUSAHAAN',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],                  // condition (cari berdasarkan nama)
                ['guard_name' => 'web']                   // jika ketemu, update ini; kalau tidak, buat baru
            );
        }
    }
}
