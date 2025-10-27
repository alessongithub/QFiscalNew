<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','subscription_id','plan_id','amount','due_date','status','description','partner_id','application_fee_amount','external_preference_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function partner() { return $this->belongsTo(Partner::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    // Accessors auxiliares
    public function getStatusNameAttribute(): string
    {
        $status = $this->status;
        // Aceita numérico (0/1) ou string, garantindo backward-compatibility
        if (is_numeric($status)) {
            $status = (int) $status;
            return match($status) {
                1 => 'Pago',
                0 => 'Pendente',
                default => (string) $status,
            };
        }
        $map = [
            'paid' => 'Pago',
            'pending' => 'Pendente',
            'canceled' => 'Cancelado',
            'refunded' => 'Estornado',
        ];
        $key = strtolower((string) $status);
        return $map[$key] ?? (string) $status;
    }
}


