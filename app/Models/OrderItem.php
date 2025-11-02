<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ReturnModel;
use App\Models\ReturnItem;

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

    /**
     * Retorna quantidade devolvida deste item
     */
    public function getReturnedQuantityAttribute(): float
    {
        $returnIds = ReturnModel::where('order_id', $this->order_id)->pluck('id');
        if ($returnIds->isEmpty()) {
            return 0.0;
        }
        
        $returned = ReturnItem::whereIn('return_id', $returnIds)
            ->where('order_item_id', $this->id)
            ->sum('quantity');
            
        return (float) round($returned, 3);
    }

    /**
     * Retorna quantidade restante (vendida - devolvida)
     */
    public function getRemainingQuantityAttribute(): float
    {
        $sold = (float) $this->quantity;
        $returned = $this->returned_quantity;
        return (float) round($sold - $returned, 3);
    }

    /**
     * Verifica se item tem devolução
     */
    public function hasReturn(): bool
    {
        return $this->returned_quantity > 0;
    }

    /**
     * Relacionamento com itens de devolução
     */
    public function returnItems()
    {
        $returnIds = ReturnModel::where('order_id', $this->order_id)->pluck('id');
        return $this->hasMany(ReturnItem::class, 'order_item_id')
            ->whereIn('return_id', $returnIds);
    }
}


