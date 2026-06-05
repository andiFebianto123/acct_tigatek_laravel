<?php

namespace App\Models;

use App\Models\Client as ClientTransaction;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProformaInvoice extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    |
    */

    const WITHHOLDING_AGENT = [
        'WAPU' => 'WAPU',
        'NON_WAPU' => 'NON WAPU',
    ];

    protected $table = 'proforma_invoices';
    protected $guarded = ['id'];

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

    public function client_po()
    {
        return $this->belongsTo(ClientPo::class, 'client_po_id');
    }

    public function client()
    {
        return $this->belongsTo(ClientTransaction::class, 'client_id');
    }

    public function subkon()
    {
        return $this->belongsTo(Subkon::class, 'subkon_id');
    }

    public function proforma_invoice_details()
    {
        return $this->hasMany(ProformaInvoiceDetail::class, 'proforma_invoice_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function account_source()
    {
        return $this->belongsTo(CastAccount::class, 'account_source_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getAccountSourceLabelAttribute()
    {
        if (!$this->account_source) {
            return '-';
        }
        return '[' . $this->account_source->no_account . '] - ' . $this->account_source->name;
    }
}
