<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNoteDetail extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'delivery_note_details';
    protected $guarded = ['id'];

    public function delivery_note()
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id');
    }

    public function device_stock()
    {
        return $this->belongsTo(DeviceStock::class, 'device_stock_id');
    }
}
