<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'ean',
        'unit',
        'ncm',
        'cest',
        'cfop',
        'origin',
        'csosn',
        'cst_icms',
        'cst_pis',
        'cst_cofins',
        'aliquota_icms',
        'aliquota_pis',
        'aliquota_cofins',
        'price',
        'avg_cost',
        'stock',
        'min_stock',
        'type',
        'active',
        'fiscal_observations',
        'fiscal_info',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
        'aliquota_icms' => 'decimal:2',
        'aliquota_pis' => 'decimal:2',
        'aliquota_cofins' => 'decimal:2',
        'avg_cost' => 'decimal:4',
        'stock' => 'decimal:3',
        'min_stock' => 'decimal:3',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}


