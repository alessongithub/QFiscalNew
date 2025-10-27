<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tipo_nota',
        'name',
        'ncm',
        'cfop',
        'codigo_servico',
        'icms_aliquota',
        'icms_reducao_bc',
        'pis_aliquota',
        'cofins_aliquota',
        'iss_aliquota',
        'csll_aliquota',
        'inss_aliquota',
        'irrf_aliquota',
        'icmsst_modalidade',
        'icmsst_mva',
        'icmsst_aliquota',
        'icmsst_reducao_bc',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'icms_aliquota' => 'decimal:4',
        'icms_reducao_bc' => 'decimal:4',
        'pis_aliquota' => 'decimal:4',
        'cofins_aliquota' => 'decimal:4',
        'iss_aliquota' => 'decimal:4',
        'csll_aliquota' => 'decimal:4',
        'inss_aliquota' => 'decimal:4',
        'irrf_aliquota' => 'decimal:4',
        'icmsst_mva' => 'decimal:4',
        'icmsst_aliquota' => 'decimal:4',
        'icmsst_reducao_bc' => 'decimal:4',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}


