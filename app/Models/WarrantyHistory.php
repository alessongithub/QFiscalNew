<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyHistory extends Model
{
    use HasFactory;
    
    protected $table = 'warranty_history';

    protected $fillable = [
        'tenant_id',
        'service_order_id',
        'order_item_id',
        'service_order_item_id',
        'serial_number',
        'warranty_start',
        'warranty_until',
        'warranty_type',
        'reason',
        'technician_id',
        'recurrence_count',
        'is_supplier_warranty',
        'supplier_status',
    ];

    protected $casts = [
        'warranty_start' => 'date',
        'warranty_until' => 'date',
        'is_supplier_warranty' => 'boolean',
        'recurrence_count' => 'integer',
    ];

    // Relacionamentos
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function serviceOrderItem(): BelongsTo
    {
        return $this->belongsTo(ServiceOrderItem::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // Accessors
    public function getWarrantyTypeLabelAttribute(): string
    {
        return match($this->warranty_type) {
            'standard' => 'Padrão',
            'extended' => 'Estendida',
            'supplier' => 'Fornecedor',
            default => 'Desconhecido'
        };
    }

    public function getSupplierStatusLabelAttribute(): string
    {
        return match($this->supplier_status) {
            'awaiting_return' => 'Aguardando Retorno',
            'waiting_replacement' => 'Aguardando Reposição',
            'returned_to_customer' => 'Devolvido ao Cliente',
            default => 'N/A'
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->warranty_until < now()->toDateString();
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return now()->diffInDays($this->warranty_until, false);
    }
}