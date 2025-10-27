<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'occurrence_type',
        'description',
        'created_by',
        'is_internal',
        'priority',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    // Relacionamentos
    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors para nomes em português
    public function getOccurrenceTypeNameAttribute()
    {
        $types = [
            'client_contact' => 'Contato com Cliente',
            'status_change' => 'Mudança de Status',
            'technical_note' => 'Nota Técnica',
            'warranty_issue' => 'Problema na Garantia',
            'delivery_note' => 'Nota de Entrega',
            'payment_note' => 'Nota de Pagamento',
            'other' => 'Outros'
        ];

        return $types[$this->occurrence_type] ?? $this->occurrence_type;
    }

    public function getPriorityNameAttribute()
    {
        $priorities = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'urgent' => 'Urgente'
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'bg-gray-100 text-gray-800',
            'medium' => 'bg-blue-100 text-blue-800',
            'high' => 'bg-yellow-100 text-yellow-800',
            'urgent' => 'bg-red-100 text-red-800'
        ];

        return $colors[$this->priority] ?? 'bg-gray-100 text-gray-800';
    }

    public function getTypeColorAttribute()
    {
        $colors = [
            'client_contact' => 'bg-green-100 text-green-800',
            'status_change' => 'bg-blue-100 text-blue-800',
            'technical_note' => 'bg-purple-100 text-purple-800',
            'warranty_issue' => 'bg-orange-100 text-orange-800',
            'delivery_note' => 'bg-indigo-100 text-indigo-800',
            'payment_note' => 'bg-yellow-100 text-yellow-800',
            'other' => 'bg-gray-100 text-gray-800'
        ];

        return $colors[$this->occurrence_type] ?? 'bg-gray-100 text-gray-800';
    }

    // Scopes para filtros
    public function scopeByType($query, $type)
    {
        return $query->where('occurrence_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
}