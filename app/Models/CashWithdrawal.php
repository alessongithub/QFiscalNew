<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','date','amount','reason','type','created_by','reversed_by','reversed_at','updated_by'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function user() { return $this->belongsTo(User::class, 'created_by'); }
    public function reversedByUser() { return $this->belongsTo(User::class, 'reversed_by'); }
    public function updatedByUser() { return $this->belongsTo(User::class, 'updated_by'); }
    
    /**
     * Verifica se a sangria pode ser editada/excluída
     */
    public function canBeModified(): bool
    {
        $dailyCash = \App\Models\DailyCash::where('tenant_id', $this->tenant_id)
            ->whereDate('date', $this->date)
            ->first();
            
        return $dailyCash ? $dailyCash->isOpen() : true;
    }
    
    /**
     * Verifica se é uma sangria normal
     */
    public function isNormal(): bool
    {
        return $this->type === 'normal';
    }
    
    /**
     * Verifica se é um estorno
     */
    public function isReversal(): bool
    {
        return $this->type === 'reversal';
    }
    
    /**
     * Cria um estorno desta sangria
     */
    public function createReversal($reason = null): self
    {
        return self::create([
            'tenant_id' => $this->tenant_id,
            'date' => $this->date,
            'amount' => $this->amount,
            'reason' => $reason ?? "Estorno: {$this->reason}",
            'type' => 'reversal',
            'created_by' => auth()->id(),
            'reversed_by' => auth()->id(),
            'reversed_at' => now(),
        ]);
    }
}

?>


