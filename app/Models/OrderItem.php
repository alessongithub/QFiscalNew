<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','order_id','product_id','name','description','quantity','unit','unit_price','discount_value','addition_value','line_total'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'addition_value' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
}


