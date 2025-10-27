<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'service_order_id',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'impact_analysis',
        'stock_reversed',
        'payments_reversed',
        'warranties_cancelled',
        'notes',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'impact_analysis' => 'array',
        'stock_reversed' => 'boolean',
        'payments_reversed' => 'boolean',
        'warranties_cancelled' => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Accessors
    public function getCancellationReasonAttribute($value)
    {
        return $value;
    }

    public function getImpactAnalysisAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setImpactAnalysisAttribute($value)
    {
        $this->attributes['impact_analysis'] = is_array($value) ? json_encode($value) : $value;
    }
}