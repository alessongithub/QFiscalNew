<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'service_order_id',
        'movement_type',
        'quantity',
        'reason',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getMovementTypeLabelAttribute()
    {
        return $this->movement_type === 'in' ? 'Entrada' : 'Saída';
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 2, ',', '.');
    }
}


