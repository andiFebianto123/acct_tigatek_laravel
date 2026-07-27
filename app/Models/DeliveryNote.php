<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    |
    */

    protected $table = 'delivery_notes';
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

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function client_po()
    {
        return $this->belongsTo(ClientPo::class, 'client_po_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function invoice_client()
    {
        return $this->belongsTo(InvoiceClient::class, 'invoice_client_id');
    }

    /**
     * Relasi polymorphic ke dokumen referensi (Quotation / Proforma Invoice / Client PO / Invoice Client).
     * Menggunakan reference_type & reference_id untuk membaca nama dokumen referensi pada tampilan list.
     */
    public function getReferenceNumberAttribute(): ?string
    {
        return match ($this->reference_type) {
            'quotation'        => optional(ClientQuotation::find($this->reference_id))->po_number,
            'proforma_invoice' => optional(ProformaInvoiceClient::find($this->reference_id))->invoice_number,
            'client_po'        => optional(ClientPo::find($this->reference_id))->po_number,
            'invoice_client'   => optional(InvoiceClient::find($this->reference_id))->invoice_number,
            default            => null,
        };
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
