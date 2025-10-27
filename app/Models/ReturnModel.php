<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnModel extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'tenant_id','order_id','total_refund','refund_method','notes'
    ];

    protected $casts = [
        'total_refund' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function items() { return $this->hasMany(ReturnItem::class, 'return_id'); }
}


