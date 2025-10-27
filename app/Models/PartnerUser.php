<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class PartnerUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'partner_users';

    protected $fillable = [
        'partner_id','name','email','password','invite_token'
    ];

    protected $hidden = ['password','remember_token'];

    public function partner() { return $this->belongsTo(Partner::class); }
}


