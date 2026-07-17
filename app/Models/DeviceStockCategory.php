<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceStockCategory extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'device_stock_categories';
    protected $guarded = ['id'];

    public function stocks()
    {
        return $this->hasMany(DeviceStock::class, 'category_id');
    }
}
