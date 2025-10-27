<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','quote_id','product_id','name','description','delivery_date','quantity','unit','unit_price','discount_value','addition_value','line_total'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'addition_value' => 'decimal:2',
        'line_total' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    public function quote() { return $this->belongsTo(Quote::class); }
    public function product() { return $this->belongsTo(Product::class); }
}


