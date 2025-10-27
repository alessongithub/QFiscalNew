<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id','method','status','amount','paid_at','metadata','partner_id','application_fee_amount'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function partner() { return $this->belongsTo(Partner::class); }
}


