<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NcmRuleAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'ncm_rule_id',
        'user_id',
        'action',
        'changes',
        'notes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function rule()
    {
        return $this->belongsTo(NcmRule::class, 'ncm_rule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


