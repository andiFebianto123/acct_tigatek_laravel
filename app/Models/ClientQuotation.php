<?php

namespace App\Models;

use App\Http\Controllers\Operation\CrudTrait;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientQuotation extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'client_quotations';
    protected $guarded = ['id'];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
