<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantBalance extends Model
{
    protected $fillable = [
        'tenant_id',
        'receivable_id',
        'gross_amount',
        'mp_fee_amount',
        'platform_fee_amount',
        'net_amount',
        'status',
        'payment_received_at',
        'available_at',
        'requested_at',
        'transferred_at',
        'transfer_method',
        'transfer_account',
        'transfer_reference',
        'transfer_notes',
        'mp_payment_id',
        'mp_transfer_id',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'mp_fee_amount' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_received_at' => 'datetime',
        'available_at' => 'datetime',
        'requested_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Status helpers
    public function markAsAvailable(): void
    {
        $this->update([
            'status' => 'available',
            'available_at' => now(),
        ]);
    }

    public function requestTransfer(): void
    {
        $this->update([
            'status' => 'requested',
            'requested_at' => now(),
        ]);
    }

    public function markAsTransferred(string $transferReference, string $transferMethod = 'pix'): void
    {
        $this->update([
            'status' => 'transferred',
            'transferred_at' => now(),
            'transfer_method' => $transferMethod,
            'transfer_reference' => $transferReference,
        ]);
    }
}


