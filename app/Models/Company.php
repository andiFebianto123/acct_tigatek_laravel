<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;

    protected $table = 'companies';
    protected $guarded = ['id'];
    protected $fillable = [
        'name',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
    ];
}
