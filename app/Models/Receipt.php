<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','client_id','number','issue_date','description','amount','notes','status','receivable_id','canceled_at','canceled_by','cancel_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'canceled_at' => 'datetime',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function receivable() { return $this->belongsTo(Receivable::class); }
}


