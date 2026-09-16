<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProformaInvoiceDetail extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'proforma_invoice_details';
    protected $guarded = ['id'];
    protected $appends = ['device_stock_id'];

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getDeviceStockIdAttribute()
    {
        return $this->attributes['reference_id'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function proforma_invoice()
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }

    public function device_stock()
    {
        return $this->belongsTo(DeviceStock::class, 'reference_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
