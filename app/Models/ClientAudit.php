<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','user_id','client_id','action','changes'
    ];

    protected $casts = [ 'changes' => 'array' ];
}


