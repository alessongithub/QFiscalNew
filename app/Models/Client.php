<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'cpf_cnpj',
        'ie_rg',
        'type',
        'address',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'codigo_ibge',
        'consumidor_final',
        'observations',
        'status',
        'tenant_id'
    ];

    protected $casts = [
        'status' => 'string',
        'type' => 'string',
    ];

    // Relacionamentos
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Mutators
    public function setCpfCnpjAttribute($value)
    {
        $this->attributes['cpf_cnpj'] = preg_replace('/\D/', '', $value);
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = preg_replace('/\D/', '', $value);
    }

    public function setZipCodeAttribute($value)
    {
        $this->attributes['zip_code'] = preg_replace('/\D/', '', $value);
    }

    public function setCodigoIbgeAttribute($value)
    {
        $this->attributes['codigo_ibge'] = preg_replace('/\D/', '', $value);
    }

    // Accessors
    public function getFormattedCpfCnpjAttribute()
    {
        $value = $this->cpf_cnpj;
        if (strlen($value) == 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value);
        } elseif (strlen($value) == 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $value);
        }
        return $value;
    }

    public function getFormattedPhoneAttribute()
    {
        $value = $this->phone;
        if (strlen($value) == 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $value);
        } elseif (strlen($value) == 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $value);
        }
        return $value;
    }

    public function getFormattedZipCodeAttribute()
    {
        $value = preg_replace('/\D/', '', (string) $this->zip_code);
        if (strlen($value) === 8) {
            return substr($value, 0, 5) . '-' . substr($value, 5);
        }
        return $this->zip_code;
    }

    public function getTypeNameAttribute()
    {
        return $this->type === 'pf' ? 'Pessoa Física' : 'Pessoa Jurídica';
    }

    public function getStatusNameAttribute()
    {
        return $this->status === 'active' ? 'Ativo' : 'Inativo';
    }

    public function getConsumidorFinalNameAttribute()
    {
        return $this->consumidor_final === 'S' ? 'Sim' : 'Não';
    }
}