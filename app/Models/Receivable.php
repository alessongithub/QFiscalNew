<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'order_id',
        'service_order_id',
        'description',
        'amount',
        'due_date',
        'status',
        'received_at',
        'payment_method',
        'tpag_override',
        'tpag_hint',
        'document_number',
        'boleto_mp_id',
        'boleto_url',
        'boleto_pdf_url',
        'boleto_barcode',
        'boleto_emitted_at',
        'pix_mp_id',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_emitted_at',
        'created_by',
        'updated_by',
        'received_by',
        'reversed_by',
        'reversed_at',
        'reverse_reason',
        'canceled_by',
        'canceled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'received_at' => 'datetime',
        'boleto_emitted_at' => 'datetime',
        'pix_emitted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function serviceOrder() { return $this->belongsTo(ServiceOrder::class); }
    
    // Relacionamentos para auditoria
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function receivedBy() { return $this->belongsTo(User::class, 'received_by'); }
    public function reversedBy() { return $this->belongsTo(User::class, 'reversed_by'); }
    public function canceledBy() { return $this->belongsTo(User::class, 'canceled_by'); }
}


