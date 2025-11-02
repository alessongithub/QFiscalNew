<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptAudit extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'receipt_id',
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

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}


