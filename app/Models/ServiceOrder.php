<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'original_service_order_id',
        'number',
        'title',
        'description',
        // assistência técnica
        'equipment_brand', 'equipment_model', 'equipment_serial', 'equipment_description', 'defect_reported',
        // aprovação/orçamento
        'diagnosis', 'budget_amount', 'approval_status', 'approval_notes', 'received_by_user_id',
        'internal_notes', 'technician_user_id', 'is_warranty',
        'status', // open, in_progress (orçada), finished, canceled, warranty
        'total_amount',
        'discount_total',
        'addition_total',
        'warranty_days',
        'warranty_until',
        'override_warranty_days',
        'warranty_notes',
        'is_supplier_warranty',
        'finalized_at',
        'issue_nfse',
        'created_by',
        'updated_by',
        'quoted_by',
        'quoted_at',
        // Campos de finalização
        'finalization_notes',
        'delivery_method',
        'delivered_by',
        'client_signature',
        'equipment_condition',
        'accessories_included',
        'final_amount',
        'payment_method',
        'payment_received',
        'finalized_by',
        // aprovação via sistema/email
        'approved_at', 'approved_by_email', 'approval_method',
        'rejected_at', 'rejected_by_email', 'rejection_method',
        'notified_at',
        // campos de cancelamento
        'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'addition_total' => 'decimal:2',
        'warranty_days' => 'integer',
        'warranty_until' => 'date',
        'quoted_at' => 'datetime',
        'issue_nfse' => 'boolean',
        'is_warranty' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'notified_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function receivables() { return $this->hasMany(Receivable::class); }
    public function items() { return $this->hasMany(ServiceOrderItem::class); }
    public function attachments() { return $this->hasMany(ServiceOrderAttachment::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_user_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function quotedBy() { return $this->belongsTo(User::class, 'quoted_by'); }
    public function statusLogs() { return $this->hasMany(ServiceOrderStatusLog::class)->orderBy('changed_at', 'desc'); }
    public function audits() { return $this->hasMany(ServiceOrderAudit::class)->orderBy('created_at', 'desc'); }
    public function occurrences() { return $this->hasMany(ServiceOrderOccurrence::class)->orderBy('created_at', 'desc'); }
    public function deliveredBy() { return $this->belongsTo(User::class, 'delivered_by'); }
    public function finalizedBy() { return $this->belongsTo(User::class, 'finalized_by'); }
    public function cancellation() { return $this->hasOne(ServiceOrderCancellation::class); }
    public function cancelledBy() { return $this->belongsTo(User::class, 'cancelled_by'); }
    
    // Relacionamentos de Garantia
    public function warrantyHistory() { return $this->hasMany(WarrantyHistory::class); }
    public function warrantyLogs() { return $this->hasMany(ServiceOrderWarrantyLog::class)->orderBy('created_at', 'desc'); }
}


