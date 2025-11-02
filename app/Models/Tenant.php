<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'logo_path',
        'fantasy_name',
        'email',
        'cnpj',
        'phone',
        'address',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'database_name',
        'domain',
        'status',
        'plan_id',
        'plan_expires_at',
        'active',
        'partner_id'
    ];

    protected $casts = [
        'status' => 'string',
        'active' => 'boolean',
        'plan_expires_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

	public function partner()
	{
		return $this->belongsTo(Partner::class);
	}

    // Relacionamentos
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function storageUsage()
    {
        return $this->hasOne(TenantStorageUsage::class);
    }

    public function storageAddons()
    {
        return $this->hasMany(StorageAddon::class)->where('status', 'active');
    }

    // Mutators
    public function setCnpjAttribute($value)
    {
        $this->attributes['cnpj'] = preg_replace('/[^0-9]/', '', $value);
    }

    public function setZipCodeAttribute($value)
    {
        $this->attributes['zip_code'] = preg_replace('/[^0-9]/', '', $value);
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace('/[^0-9]/', '', $value);
    }

    // Accessors
    public function getFormattedCnpjAttribute()
    {
        $cnpj = $this->cnpj;
        return substr($cnpj, 0, 2) . '.' . 
               substr($cnpj, 2, 3) . '.' . 
               substr($cnpj, 5, 3) . '/' . 
               substr($cnpj, 8, 4) . '-' . 
               substr($cnpj, 12, 2);
    }

    public function getFormattedPhoneAttribute()
    {
        $phone = $this->phone;
        if(strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 5) . '-' . 
                   substr($phone, 7);
        }
        return '(' . substr($phone, 0, 2) . ') ' . 
               substr($phone, 2, 4) . '-' . 
               substr($phone, 6);
    }

    public function getFormattedZipCodeAttribute()
    {
        $cep = $this->zip_code;
        return substr($cep, 0, 5) . '-' . substr($cep, 5);
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) {
            return null;
        }
        
        return asset('storage/' . $this->logo_path);
    }
}