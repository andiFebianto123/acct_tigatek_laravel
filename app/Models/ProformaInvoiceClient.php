<?php

namespace App\Models;

use App\Models\Client as ClientTransaction;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProformaInvoiceClient extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'proforma_invoice_clients';
    protected $guarded = ['id'];

    public function client_po()
    {
        return $this->belongsTo(ClientPo::class, 'client_po_id');
    }

    public function clientQuotation()
    {
        return $this->belongsTo(ClientQuotation::class, 'client_quotation_id');
    }

    public function client()
    {
        return $this->belongsTo(ClientTransaction::class, 'client_id');
    }

    public function proforma_invoice_client_details()
    {
        return $this->hasMany(ProformaInvoiceClientDetail::class, 'proforma_invoice_client_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function account_source()
    {
        return $this->belongsTo(CastAccount::class, 'account_source_id');
    }

    public function getAccountSourceLabelAttribute()
    {
        if (!$this->account_source) {
            return '-';
        }
        return '[' . $this->account_source->no_account . '] - ' . $this->account_source->name;
    }
}
