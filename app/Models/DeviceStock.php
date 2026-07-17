<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceStock extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'device_stocks';
    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(DeviceStockCategory::class, 'category_id');
    }
}
