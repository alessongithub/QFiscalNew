<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'supplier_name',
        'description',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'payment_method',
        'document_number',
        'created_by',
        'updated_by',
        'paid_by',
        'reversed_by',
        'reversed_at',
        'reverse_reason',
        'cancel_reason',
        'canceled_at',
        'canceled_by',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'reversed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    
    // Relacionamentos para auditoria
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function paidBy() { return $this->belongsTo(User::class, 'paid_by'); }
    public function reversedBy() { return $this->belongsTo(User::class, 'reversed_by'); }
    public function canceledBy() { return $this->belongsTo(User::class, 'canceled_by'); }
    public function deletedBy() { return $this->belongsTo(User::class, 'deleted_by'); }
}


