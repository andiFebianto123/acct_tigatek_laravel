<?php

namespace App\Models;

use App\Models\Client as ClientTransaction;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceClient extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    const WITHHOLDING_AGENT = [
        'WAPU' => 'WAPU',
        'NON_WAPU' => 'NON WAPU',
    ];

    protected $table = 'invoice_clients';
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

    function client_po()
    {
        return $this->belongsTo(ClientPo::class, 'client_po_id');
    }

    function client()
    {
        return $this->belongsTo(ClientTransaction::class, 'client_id');
    }

    function invoice_client_details()
    {
        return $this->hasMany(InvoiceClientDetail::class, 'invoice_client_id');
    }

    function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    function account_source()
    {
        return $this->belongsTo(CastAccount::class, 'account_source_id');
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

    public function getAccountSourceLabelAttribute()
    {
        if (!$this->account_source) return '-';
        return '[' . $this->account_source->no_account . '] - ' . $this->account_source->name;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
