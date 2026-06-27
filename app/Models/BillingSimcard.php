<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BillingSimcard extends Model
{
    use CrudTrait;
    use SoftDeletes;

    protected $table = 'billing_simcards';

    protected $fillable = [
        'company_id',
        'product',
        'device_name',
        'technology',
        'device_profile_id',
        'iccid',
        'msisdn',
        'status',
        'rate_plan',
        'subscription_expiry_date',
        'installation_date',
        'expired_date',
        'reminder_date',
    ];

    protected $casts = [
        'subscription_expiry_date' => 'date',
        'installation_date' => 'date',
        'expired_date' => 'date',
        'reminder_date' => 'date',
    ];

    protected $appends = [
        'simcard_status',
    ];

    /**
     * Relationship to Company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Accessor for dynamic Status SIMCARD.
     * Active: expired_date is today or in the future.
     * Deactive: expired_date is in the past, or empty.
     */
    public function getSimcardStatusAttribute(): string
    {
        if (empty($this->expired_date)) {
            return 'Deactive';
        }
        
        $expiredDate = Carbon::parse($this->expired_date)->startOfDay();
        $today = Carbon::now()->startOfDay();

        return $expiredDate->gte($today) ? 'Active' : 'Deactive';
    }
}
