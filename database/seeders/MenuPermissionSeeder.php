<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MenuPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'MENU INDEX DASHBOARD',
            'MENU INDEX VENDOR DAFTAR SUBKON',
            'MENU INDEX VENDOR PO',
            'MENU INDEX VENDOR SPK',
            'MENU INDEX CLIENT DAFTAR CLIENT',
            'MENU INDEX CLIENT PO',
            'MENU INDEX CLIENT QUOTATION',
            'MENU INDEX FA VOUCHER',
            'MENU INDEX FA PEMBAYARAN',
            'MENU INDEX FA RENCANA PEMBAYARAN',
            'MENU INDEX RENCANA PEMBAYARAN',
            'MENU INDEX ARUS REKENING KAS',
            'MENU INDEX ARUS REKENING PINJAMAN',
            'MENU INDEX LAPORAN KEUANGAN COA',
            'MENU INDEX LAPORAN KEUANGAN LABA RUGI',
            'MENU INDEX LAPORAN KEUANGAN NERACA',
            'MENU INDEX LAPORAN KEUANGAN DAFTAR ASET',
            'MENU INDEX INVOICE',
            'MENU INDEX MONITORING PROYEK STATUS PROYEK',
            'MENU INDEX MONITORING PROYEK STATUS PENAWARAN',
            'MENU INDEX MONITORING PROYEK PROYEKSI PEKERJAAN',
            'MENU INDEX MONITORING PROYEK DAFTAR PROYEK',
            'MENU INDEX MONITORING PROYEK DAFTAR PENAWARAN',
            'MENU INDEX MONITORING PROYEK PROYEK REPORT',
            'MENU INDEX MONITORING PROYEK PROYEK SYSTEM SETUP',
            'MENU INDEX PENGATURAN USER',
            'MENU INDEX PENGATURAN ROLE',
            'MENU INDEX PENGATURAN PERMISSION',
            'MENU INDEX PENGATURAN SISTEM',
            'MENU INDEX PENGATURAN AKUN',
            'MENU INDEX MONITORING TRACKER',
            'MENU INDEX CLIENT SURAT JALAN',
            'MENU INDEX CLIENT BAST',
            'MENU INDEX CLIENT BILLING DEVICE',
            'MENU INDEX CLIENT BILLING SIMCARD',
            'MENU INDEX VENDOR PROFORMA INVOICE',
        ];

        $permissionModels = [];

        foreach ($permissions as $permission) {
            $originPermission = $permission;
            $createPermission = str_replace('MENU', 'CREATE', $originPermission);
            $updatePermission = str_replace('MENU', 'UPDATE', $originPermission);
            $deletePermission = str_replace('MENU', 'DELETE', $originPermission);
            
            $permissionModels[] = Permission::updateOrCreate(
                ['name' => $originPermission],                  // condition (cari berdasarkan nama)
                [
                    'name' => $originPermission,
                    'guard_name' => 'web'
                ]                   // jika ketemu, update ini; kalau tidak, buat baru
            );
            $permissionModels[] = Permission::updateOrCreate(
                ['name' => $createPermission],                  // condition (cari berdasarkan nama)
                [
                    'name' => $createPermission,
                    'guard_name' => 'web'
                ]                   // jika ketemu, update ini; kalau tidak, buat baru
            );
            $permissionModels[] = Permission::updateOrCreate(
                ['name' => $updatePermission],                  // condition (cari berdasarkan nama)
                [
                    'name' => $updatePermission,
                    'guard_name' => 'web'
                ]                   // jika ketemu, update ini; kalau tidak, buat baru
            );
            $permissionModels[] = Permission::updateOrCreate(
                ['name' => $deletePermission],                  // condition (cari berdasarkan nama)
                [
                    'name' => $deletePermission,
                    'guard_name' => 'web'
                ]                   // jika ketemu, update ini; kalau tidak, buat baru
            );
        }

        // Assign to Super Admin role
        $role = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
        if ($role) {
            $role->givePermissionTo($permissionModels);
        }
    }
}
