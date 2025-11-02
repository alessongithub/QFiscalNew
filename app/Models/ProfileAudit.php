<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileAudit extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id','user_id','action','changes','notes'];
    protected $casts = ['changes' => 'array'];
}


