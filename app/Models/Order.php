<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ReturnModel;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'number',
        'title',
        'status',
        'canceled_at',
        'canceled_by',
        'cancel_reason',
        'total_amount',
        'discount_total',
        'addition_total',
        'carrier_id',
        'freight_mode',
        'freight_payer',
        'freight_cost',
        'freight_obs',
        'additional_info',
        'fiscal_info',
        'volume_qtd',
        'volume_especie',
        'peso_bruto',
        'peso_liquido',
        'valor_seguro',
        'outras_despesas',
        'nfe_issued_at',
        'reopen_preserve_financial',
        'created_by',
        'updated_by',
        'last_edited_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'addition_total' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'peso_bruto' => 'decimal:3',
        'peso_liquido' => 'decimal:3',
        'valor_seguro' => 'decimal:2',
        'outras_despesas' => 'decimal:2',
        'freight_mode' => 'integer',
        'nfe_issued_at' => 'datetime',
        'canceled_at' => 'datetime',
        'reopen_preserve_financial' => 'boolean',
        'last_edited_at' => 'datetime',
    ];

    public function items() { return $this->hasMany(OrderItem::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function carrier() { return $this->belongsTo(Carrier::class); }
    public function receivables() { return $this->hasMany(Receivable::class); }

    // Notas fiscais vinculadas ao pedido
    public function nfeNotes()
    {
        return $this->hasMany(\App\Models\NfeNote::class, 'order_id');
    }

    public function latestNfeNote()
    {
        return $this->hasOne(\App\Models\NfeNote::class, 'order_id')->latestOfMany();
    }

    // Compat: retorna a última NFe por order_id (se existir) ou por numero_pedido
    public function getLatestNfeNoteCompatAttribute()
    {
        try {
            $hasOrderId = \Illuminate\Support\Facades\Schema::hasColumn('nfe_notes', 'order_id');
            $hasNumeroPedido = \Illuminate\Support\Facades\Schema::hasColumn('nfe_notes', 'numero_pedido');
        } catch (\Throwable $e) {
            $hasOrderId = true; // assume novo schema
            $hasNumeroPedido = true;
        }

        $q = \App\Models\NfeNote::query()->where('tenant_id', $this->tenant_id);
        if ($hasOrderId && $hasNumeroPedido) {
            $q->where(function($qq){
                $qq->where('order_id', $this->id)->orWhere('numero_pedido', (string)$this->number);
            });
        } elseif ($hasOrderId) {
            $q->where('order_id', $this->id);
        } elseif ($hasNumeroPedido) {
            $q->where('numero_pedido', (string)$this->number);
        } else {
            return null;
        }
        return $q->orderByDesc('id')->first();
    }

    // Normaliza status da última NFe vinculada
    public function getLatestNfeStatusAttribute(): ?string
    {
        try {
            $note = $this->latestNfeNoteCompat;
            if (!$note) { return null; }
            $st = strtolower(trim((string) $note->status));
            // Normalizações comuns
            $map = [
                'transmitida' => 'authorized',
                'emitted' => 'authorized',
                'autorizada' => 'authorized',
                'authorized' => 'authorized',
                'approved' => 'authorized',
                'aprovada' => 'authorized',
                'com_cc' => 'authorized',
                'cce' => 'authorized',
                'carta_correcao' => 'authorized',
                'corrigida' => 'authorized',
                'cancelada' => 'canceled',
                'cancelled' => 'canceled',
                'canceled' => 'canceled',
                'error' => 'rejected',
                'rejeitada' => 'rejected',
                'rejected' => 'rejected',
                'pending' => 'pending',
            ];
            return $map[$st] ?? $st;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getHasSuccessfulNfeAttribute(): bool
    {
        $st = $this->latest_nfe_status;
        return in_array($st, ['authorized'], true);
    }

    public function getHasCancelledNfeAttribute(): bool
    {
        $st = $this->latest_nfe_status;
        return in_array($st, ['canceled'], true);
    }

    // Relacionamentos de auditoria
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function audits()
    {
        return $this->hasMany(OrderAudit::class);
    }

    /**
     * Verifica se pedido pode ser reaberto
     * Bloqueia se há NFe transmitida (autorizada)
     */
    public function canBeReopened(): bool
    {
        // Usa o accessor hasSuccessfulNfe que verifica se há NF-e autorizada
        if ($this->has_successful_nfe) {
            return false;
        }
        
        // Só pode reabrir se estiver finalizado ou com devolução parcial
        return in_array($this->status, ['fulfilled', 'partial_returned'], true);
    }

    /**
     * Retorna itens com devoluções (parciais ou totais)
     * Retorna Collection com dados de cada item afetado
     */
    public function getItemsWithPartialReturns(): \Illuminate\Support\Collection
    {
        $items = collect();
        
        foreach ($this->items as $item) {
            $returnedQty = $item->returned_quantity;
            $remainingQty = $item->remaining_quantity;
            
            // Inclui se tem devolução (parcial ou total)
            // Parcial: tem devolução mas ainda tem quantidade restante
            // Total: foi totalmente devolvido (remaining = 0)
            if ($returnedQty > 0) {
                $items->push([
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'sold' => (float) $item->quantity,
                    'returned' => $returnedQty,
                    'remaining' => $remainingQty,
                    'has_discount' => ((float)($item->discount_value ?? 0)) > 0,
                    'discount_value' => (float) ($item->discount_value ?? 0),
                    'unit_price' => (float) $item->unit_price,
                ]);
            }
        }
        
        return $items;
    }

    /**
     * Calcula totais ajustados considerando devoluções
     * Retorna array com ['subtotal', 'discount', 'addition', 'total']
     */
    public function getAdjustedTotals(): array
    {
        $subtotal = 0;
        $discount = 0;
        $addition = 0;
        
        foreach ($this->items as $item) {
            $remainingQty = $item->remaining_quantity;
            
            // Se item foi totalmente devolvido, pula
            if ($remainingQty <= 0) {
                continue;
            }
            
            $lineTotal = $remainingQty * (float) $item->unit_price;
            $subtotal += $lineTotal;
            
            // Desconto é zerado para itens com devolução parcial (política atual)
            // Pode ser ajustado para proporcional se necessário
            // $discount += ($remainingQty / (float) $item->quantity) * (float) ($item->discount_value ?? 0);
            
            // Acréscimo também zerado (mesma política)
            // $addition += ($remainingQty / (float) $item->quantity) * (float) ($item->addition_value ?? 0);
        }
        
        $total = $subtotal - $discount + $addition;
        
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'addition' => round($addition, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Relacionamento com devoluções
     */
    public function returns()
    {
        return $this->hasMany(ReturnModel::class, 'order_id');
    }
}


