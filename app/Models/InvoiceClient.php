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

    function delivery_note()
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id');
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

    /**
     * Resolusi Model ClientPo berdasarkan kondisi type_device:
     * - Jika bukan Persediaan: ambil langsung dari relasi client_po ($this->client_po).
     * - Jika Persediaan: ambil dari delivery_note (jika reference_type == 'client_po' menggunakan reference_id, atau fallback client_po_id).
     */
    public function getResolvedClientPoAttribute()
    {
        if ($this->type_device === 'App\Models\DeviceStock') {
            if ($this->delivery_note) {
                if ($this->delivery_note->reference_type === 'client_po' && $this->delivery_note->reference_id) {
                    return ClientPo::find($this->delivery_note->reference_id);
                }
                if ($this->delivery_note->client_po_id) {
                    return $this->delivery_note->client_po;
                }
            }
        }

        return $this->client_po;
    }

    /**
     * Ambil Nomor PO Klien yang sudah terselesaikan.
     */
    public function getClientPoNumberAttribute()
    {
        return $this->resolved_client_po?->po_number ?? '-';
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
