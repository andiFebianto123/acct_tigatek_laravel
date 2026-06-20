<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionHistory extends Model
{
    use CrudTrait;
    use SoftDeletes;

    protected $table = 'transaction_histories';

    protected $fillable = [
        'company_id',
        'transaction_id',
        'device_id',
        'msisdn',
        'op_completion_time',
        'operations',
        'devices_upload',
        'device_prosses',
        'device_update',
        'last_update',
        'status',
    ];

    protected $casts = [
        'op_completion_time' => 'datetime',
        'last_update' => 'datetime',
        'devices_upload' => 'integer',
        'device_prosses' => 'integer',
        'device_update' => 'integer',
    ];

    /**
     * Relationship to Company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
