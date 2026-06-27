<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingNotification extends Model
{
    use CrudTrait;
    use SoftDeletes;

    protected $table = 'billing_notifications';

    protected $fillable = [
        'company_id',
        'billable_type',
        'billable_id',
        'notification_date',
        'message',
    ];

    protected $casts = [
        'notification_date' => 'date',
    ];

    protected $appends = [
        'billable_type_label',
        'billable_target',
    ];

    /**
     * Relationship to Company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Polymorphic relationship.
     */
    public function billable()
    {
        return $this->morphTo();
    }

    /**
     * Accessor for human-readable billable type.
     */
    public function getBillableTypeLabelAttribute(): string
    {
        if ($this->billable_type === \App\Models\BillingDevice::class) {
            return 'Device';
        }
        if ($this->billable_type === \App\Models\BillingSimcard::class) {
            return 'SIMCARD';
        }
        return 'Lainnya';
    }

    /**
     * Accessor for target billable item description/identifier.
     */
    public function getBillableTargetAttribute(): string
    {
        if ($this->billable) {
            if ($this->billable_type === \App\Models\BillingDevice::class) {
                return "Device ID: " . ($this->billable->device_id ?? '-');
            }
            if ($this->billable_type === \App\Models\BillingSimcard::class) {
                return "MSISDN: " . ($this->billable->msisdn ?? '-');
            }
        }
        return 'ID: ' . $this->billable_id;
    }
}
