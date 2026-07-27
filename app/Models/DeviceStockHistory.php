<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceStockHistory extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'device_stock_histories';
    protected $guarded = ['id'];

    public function deviceStock()
    {
        return $this->belongsTo(DeviceStock::class, 'device_stock_id');
    }

    public function mutations()
    {
        return $this->hasMany(DeviceStockMutation::class, 'device_stock_history_id');
    }
}
