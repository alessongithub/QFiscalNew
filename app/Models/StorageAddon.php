<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageAddon extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'quantity_mb',
        'price',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'quantity_mb' => 'integer',
        'price' => 'decimal:2',
        'expires_at' => 'date'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
