<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'trade_name',
        'cnpj',
        'ie',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'vehicle_plate',
        'vehicle_state',
        'rntc',
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
