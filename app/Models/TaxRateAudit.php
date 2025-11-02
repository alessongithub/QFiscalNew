<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRateAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_rate_id',
        'user_id',
        'action',
        'changes',
        'notes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

