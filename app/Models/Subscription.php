<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','plan_id','status','current_period_start','current_period_end'
    ];

    protected $casts = [
        'current_period_start' => 'date',
        'current_period_end' => 'date',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
}


