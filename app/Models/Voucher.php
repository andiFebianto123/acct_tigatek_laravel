<?php

namespace App\Models;

use App\Models\Subkon;
use App\Models\ClientPo;
use App\Models\CastAccount;
use App\Models\Company;
use App\Models\VoucherEdit;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'vouchers';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    
    function company(){
        return $this->belongsTo(Company::class, 'company_id');
    }

    function account(){
        return $this->belongsTo(Account::class, 'account_id');
    }


    function subkon(){
        return $this->belongsTo(Subkon::class, 'subkon_id');
    }

    function client_po(){
        return $this->belongsTo(ClientPo::class, 'client_po_id', 'id');
    }

    function account_source(){
        return $this->belongsTo(CastAccount::class, 'account_source_id', 'id');
    }

    function purchase_order(){
        return $this->belongsTo(PurchaseOrder::class, 'reference_id', 'id');
    }


    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function voucher_edit(){
        return $this->hasMany(VoucherEdit::class, 'voucher_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
