<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderWarrantyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'old_status',
        'new_status',
        'warranty_days_old',
        'warranty_days_new',
        'reason',
        'user_id',
    ];

    // Relacionamentos
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getStatusChangeAttribute(): string
    {
        if ($this->old_status && $this->new_status) {
            return "{$this->old_status} → {$this->new_status}";
        }
        return $this->new_status;
    }

    public function getWarrantyDaysChangeAttribute(): string
    {
        if ($this->warranty_days_old !== null && $this->warranty_days_new !== null) {
            return "{$this->warranty_days_old} → {$this->warranty_days_new} dias";
        }
        return $this->warranty_days_new ? "{$this->warranty_days_new} dias" : 'N/A';
    }
}