<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarrierAudit extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'carrier_id',
        'action',
        'changes',
        'notes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }
}


