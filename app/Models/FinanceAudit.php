<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceAudit extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'notes',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


