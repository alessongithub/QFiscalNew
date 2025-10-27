<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','slug','domain',
        'cnpj','crc','contact_name','contact_email','contact_phone','applied_at',
        'commission_percent','theme','primary_color','secondary_color','logo_path','active',
        'mp_user_id','mp_public_key','mp_access_token','mp_refresh_token','mp_connected_at',
    ];
}


