<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceClientRecurringItem extends Model
{
    use CrudTrait;
    use SoftDeletes;

    protected $table = 'invoice_client_recurring_items';

    protected $guarded = ['id'];

    protected $casts = [
        'snapshot_data' => 'array',
    ];

    /**
     * Relationship to InvoiceClient.
     */
    public function invoice_client()
    {
        return $this->belongsTo(InvoiceClient::class, 'invoice_client_id');
    }

    /**
     * Relationship to InvoiceClientDetail.
     */
    public function invoice_client_detail()
    {
        return $this->belongsTo(InvoiceClientDetail::class, 'invoice_client_detail_id');
    }

    /**
     * Morph relation to original billable item (if still exists).
     */
    public function billable()
    {
        return $this->morphTo();
    }
}
