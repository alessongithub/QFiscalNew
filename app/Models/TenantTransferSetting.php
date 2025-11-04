<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantTransferSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'bank_name',
        'bank_code',
        'agency',
        'account',
        'account_type',
        'account_holder_name',
        'account_holder_document',
        'pix_key',
        'pix_key_type',
        'preferred_method',
        'auto_transfer_enabled',
        'auto_transfer_min_amount',
    ];

    protected $casts = [
        'auto_transfer_enabled' => 'boolean',
        'auto_transfer_min_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}


