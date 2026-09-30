<?php

namespace App\Models;

use App\Models\Client as ClientTransaction;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceClientDetail extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'invoice_client_details';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $appends = ['item_type'];
    // protected $fillable = [];
    // protected $hidden = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    function invoice_client()
    {
        return $this->belongsTo(InvoiceClient::class, 'invoice_client_id');
    }

    function deviceStock()
    {
        return $this->belongsTo(\App\Models\DeviceStock::class, 'device_stock_id');
    }

    function deliveryNoteDetail()
    {
        return $this->belongsTo(\App\Models\DeliveryNoteDetail::class, 'delivery_note_detail_id');
    }

    function recurring_items()
    {
        return $this->hasMany(\App\Models\InvoiceClientRecurringItem::class, 'invoice_client_detail_id');
    }


    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    public function getItemTypeAttribute(): ?string
    {
        if (!empty($this->attributes['item_type'])) {
            return $this->attributes['item_type'];
        }

        // Cek relation recurring_items
        $firstRecurring = $this->recurring_items?->first();
        if ($firstRecurring && !empty($firstRecurring->item_type)) {
            return strtoupper($firstRecurring->item_type);
        }

        // Fallback dari code_billing
        if (!empty($this->code_billing)) {
            $isDevice = \App\Models\BillingDevice::where('code_billing', $this->code_billing)->exists();
            return $isDevice ? 'DEVICE' : 'SIMCARD';
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
