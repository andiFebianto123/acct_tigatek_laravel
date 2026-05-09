<?php
namespace App\Models;

use App\Models\Voucher;
use App\Models\ClientPo;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class ProjectProfitLost extends Model{
    use CrudTrait;
    protected $table = 'project_profit_lost';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];

    public function clientPo(){
        return $this->belongsTo(ClientPo::class, 'client_po_id');
    }

    public function voucher(){
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function company(){
        return $this->belongsTo(Company::class, 'company_id');
    }
}
