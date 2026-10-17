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
        'code_billing',
        'notification_date',
        'message',
    ];

    protected $casts = [
        'notification_date' => 'date',
    ];

    /**
     * Relationship to Company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relationship to BillingReminder.
     */
    public function reminder()
    {
        return $this->hasOne(BillingReminder::class, 'code_billing', 'code_billing');
    }

    /**
     * Relationship to InvoiceClient.
     */
    public function invoice_clients()
    {
        return $this->hasMany(InvoiceClient::class, 'billing_notification_id');
    }
}
