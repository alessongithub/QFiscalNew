<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnAudit extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'return_id',
        'order_id',
        'action',
        'changes',
        'notes',
    ];

    protected $casts = [ 'changes' => 'array' ];

    public function user() { return $this->belongsTo(User::class); }
    public function order() { return $this->belongsTo(Order::class); }
}


