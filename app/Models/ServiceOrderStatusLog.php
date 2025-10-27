<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'old_status',
        'new_status',
        'changed_by',
        'changed_at',
        'reason',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the status name in Portuguese
     */
    public function getOldStatusNameAttribute()
    {
        $statusMap = [
            'open' => 'Em análise',
            'in_progress' => 'Orçada',
            'in_service' => 'Em andamento',
            'service_finished' => 'Serviço Finalizado',
            'warranty' => 'Garantia',
            'no_repair' => 'Sem reparo',
            'finished' => 'Finalizada',
            'canceled' => 'Cancelada',
        ];

        return $statusMap[$this->old_status] ?? $this->old_status;
    }

    public function getNewStatusNameAttribute()
    {
        $statusMap = [
            'open' => 'Em análise',
            'in_progress' => 'Orçada',
            'in_service' => 'Em andamento',
            'service_finished' => 'Serviço Finalizado',
            'warranty' => 'Garantia',
            'no_repair' => 'Sem reparo',
            'finished' => 'Finalizada',
            'canceled' => 'Cancelada',
        ];

        return $statusMap[$this->new_status] ?? $this->new_status;
    }
}