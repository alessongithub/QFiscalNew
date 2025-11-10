<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id', 'tenant_id', 'plan_id',
        'provider', 'provider_payment_id', 'status',
        'amount', 'paid_at', 'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
}


