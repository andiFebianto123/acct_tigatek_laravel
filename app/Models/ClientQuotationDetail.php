<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientQuotationDetail extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'client_quotation_details';
    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function getPriceAttribute()
    {
        return isset($this->attributes['unit_price']) ? (float) $this->attributes['unit_price'] : ($this->attributes['price'] ?? null);
    }

    public function clientQuotation()
    {
        return $this->belongsTo(ClientQuotation::class, 'client_quotation_id');
    }

    public function deviceStock()
    {
        return $this->belongsTo(DeviceStock::class, 'device_stock_id');
    }
}
