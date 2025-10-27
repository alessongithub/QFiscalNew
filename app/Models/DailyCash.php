<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCash extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'date',
        'status',
        'total_received',
        'total_paid',
        'net_total',
        'current_balance',
        'notes',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_received' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'net_total' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function closedByUser() { return $this->belongsTo(User::class, 'closed_by'); }
    
    /**
     * Verifica se o caixa está fechado
     */
    public function isClosed(): bool
    {
        return $this->

status === 'closed' || $this->closed_at !== null;
    }
    
    /**
     * Verifica se o caixa está aberto
     */
    public function isOpen(): bool
    {
        return !$this->isClosed();
    }
    
    /**
     * Atualiza o saldo atual do caixa
     */
    public function updateCurrentBalance(): void
    {
        $this->current_balance = $this->net_total - $this->getWithdrawalsTotal();
        $this->save();
    }
    
    /**
     * Calcula o total de sangrias do dia
     */
    public function getWithdrawalsTotal(): float
    {
        return \App\Models\CashWithdrawal::where('tenant_id', $this->tenant_id)
            ->whereDate('date', $this->date)
            ->where('type', 'normal')
            ->sum('amount');
    }
}


