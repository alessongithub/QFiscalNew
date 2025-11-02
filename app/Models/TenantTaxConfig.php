<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantTaxConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'regime_tributario',
        'cnae_principal',
        'anexo_simples',
        'aliquota_simples_nacional',
        'habilitar_ibpt',
        'codigo_ibpt_padrao',
        'updated_by',
    ];

    protected $casts = [
        'habilitar_ibpt' => 'boolean',
        'aliquota_simples_nacional' => 'decimal:4',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}


