<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashWithdrawalAudit extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'cash_withdrawal_id',
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

    public function withdrawal()
    {
        return $this->belongsTo(CashWithdrawal::class, 'cash_withdrawal_id');
    }
}


