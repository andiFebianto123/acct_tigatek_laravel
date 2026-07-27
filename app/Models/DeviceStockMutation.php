<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceStockMutation extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'device_stock_mutations';
    protected $guarded = ['id'];

    public function deviceStock()
    {
        return $this->belongsTo(DeviceStock::class, 'device_stock_id');
    }

    public function history()
    {
        return $this->belongsTo(DeviceStockHistory::class, 'device_stock_history_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
