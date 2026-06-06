<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use CrudTrait;
    use HasFactory;

    /**
     * Kolom-kolom yang dapat diisi secara massal (mass assignable).
     * Pastikan semua field dari form ada di sini.
     */
    protected $fillable = [
        'currency',
        'currency_symbol',
        'position_currency_symbol',
        'format_decimal_number',
        'po_prefix',
        'spk_prefix',
        'work_code_prefix',
        'vouhcer_prefix',
        'faktur_prefix',
        'invoice_prefix',
        'pi_prefix',
        'quotation_prefix',
        'surat_jalan_prefix',
        'bast_prefix',
        // Tambahkan field lain jika ada dari form
        'name_company',
        'address',
        'city',
        'province',
        'zip_code',
        'country',
        'telp',
        'no_register_company',
        'start_time',
        'end_time',
        'no_fax',

        // Fields lainnya yang mungkin tidak dari form
        'logo_dark',
        'logo_light',
        'favicon',
    ];
}
