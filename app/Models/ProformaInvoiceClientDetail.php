<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProformaInvoiceClientDetail extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'proforma_invoice_client_details';
    protected $guarded = ['id'];

    public function proforma_invoice_client()
    {
        return $this->belongsTo(ProformaInvoiceClient::class, 'proforma_invoice_client_id');
    }
}
