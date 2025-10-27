<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'number',
        'title',
        'status',
        'total_amount',
        'discount_total',
        'addition_total',
        'approved_at',
        'approved_by',
        'approval_reason',
        'notified_at',
        'not_approved_at',
        'canceled_at',
        'canceled_by',
        'cancel_reason',
        'notes',
        'validity_date',
        'payment_methods',
        'card_installments',
        'additional_info',
        'fiscal_info',
        'volume_qtd',
        'volume_especie',
        'peso_bruto',
        'peso_liquido',
        'valor_seguro',
        'outras_despesas',
        'created_by',
        'updated_by',
        'last_edited_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'addition_total' => 'decimal:2',
        'approved_at' => 'datetime',
        'notified_at' => 'datetime',
        'not_approved_at' => 'datetime',
        'canceled_at' => 'datetime',
        'validity_date' => 'date',
        'payment_methods' => 'array',
        'card_installments' => 'integer',
        'peso_bruto' => 'decimal:3',
        'peso_liquido' => 'decimal:3',
        'valor_seguro' => 'decimal:2',
        'outras_despesas' => 'decimal:2',
        'last_edited_at' => 'datetime',
    ];

    public function items() { return $this->hasMany(QuoteItem::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function canceledBy() { return $this->belongsTo(User::class, 'canceled_by'); }
    public function audits() { return $this->hasMany(QuoteAudit::class); }
}


