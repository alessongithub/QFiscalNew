<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingsAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'setting_key',
        'old_value',
        'new_value',
        'notes',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

