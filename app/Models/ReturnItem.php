<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id','order_item_id','quantity','unit_price','line_total'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function return() { return $this->belongsTo(ReturnModel::class, 'return_id'); }
    public function orderItem() { return $this->belongsTo(OrderItem::class); }
}


