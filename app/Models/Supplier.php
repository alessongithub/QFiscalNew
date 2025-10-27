<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'trade_name',
        'cpf_cnpj',
        'ie_rg',
        'email',
        'phone',
        'address',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}


