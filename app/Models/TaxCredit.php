<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'document_type',
        'document_number',
        'document_series',
        'document_date',
        'supplier_cnpj',
        'supplier_name',
        'base_calculo_icms',
        'valor_icms',
        'aliquota_icms',
        'cst_icms',
        'cfop',
        'ncm',
        'quantity',
        'unit_price',
        'total_value',
        'quantity_used',
        'valor_icms_used',
        'fully_used',
        'status',
        'observations',
    ];

    protected $casts = [
        'document_date' => 'date',
        'base_calculo_icms' => 'decimal:2',
        'valor_icms' => 'decimal:2',
        'aliquota_icms' => 'decimal:4',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'quantity_used' => 'decimal:3',
        'valor_icms_used' => 'decimal:2',
        'fully_used' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Verifica se há crédito disponível para o produto
     */
    public function hasAvailableCredit(): bool
    {
        return $this->status === 'active' && !$this->fully_used;
    }

    /**
     * Calcula o valor de ICMS disponível proporcionalmente à quantidade
     */
    public function getAvailableIcmsValue(float $quantity): float
    {
        if (!$this->hasAvailableCredit() || $this->quantity <= 0) {
            return 0.0;
        }

        $availableQuantity = $this->quantity - $this->quantity_used;
        $usableQuantity = min($quantity, $availableQuantity);
        
        return round(($usableQuantity / $this->quantity) * $this->valor_icms, 2);
    }

    /**
     * Marca crédito como utilizado
     */
    public function markAsUsed(float $quantity, float $icmsValue): void
    {
        $this->quantity_used += $quantity;
        $this->valor_icms_used += $icmsValue;
        
        if ($this->quantity_used >= $this->quantity) {
            $this->fully_used = true;
            $this->status = 'used';
        }
        
        $this->save();
    }

    /**
     * Scope para créditos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('fully_used', false);
    }

    /**
     * Scope para créditos de um produto específico
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
