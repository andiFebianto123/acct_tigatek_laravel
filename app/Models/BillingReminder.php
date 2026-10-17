<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingReminder extends Model
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $table = 'billing_reminders';
    protected $guarded = ['id'];

    protected $casts = [
        'installation_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_notification_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function billingDevices()
    {
        return $this->hasMany(BillingDevice::class, 'code_billing', 'code_billing');
    }

    public function billingSimcards()
    {
        return $this->hasMany(BillingSimcard::class, 'code_billing', 'code_billing');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */
    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period) {
            '1_month' => 'Sebulan',
            '3_months' => '3 Bulan',
            '6_months' => '6 Bulan',
            '1_year' => 'Setahun',
            default => $this->period ?? '-',
        };
    }
}
