<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TenantEmitter extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'cnpj','ie','razao_social','nome_fantasia',
        'phone','email',
        'zip_code','address','number','complement','neighborhood','city','state','codigo_ibge',
        'certificate_path','certificate_password_encrypted','certificate_valid_until',
        'nfe_model','nfe_serie','nfe_number_current','icms_credit_percent',
        'base_storage_disk','base_storage_path',
    ];

    protected $casts = [
        'certificate_valid_until' => 'date',
        'icms_credit_percent' => 'decimal:2',
        'nfe_number_current' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function storageDisk(): string
    {
        return $this->base_storage_disk ?: config('filesystems.default', 'local');
    }

    public function basePath(): string
    {
        $base = $this->base_storage_path ?: ('tenants/'.$this->tenant_id.'/nfe');
        return trim($base,'/');
    }

    public function pathXml(): string { return $this->basePath().'/xml'; }
    public function pathDanfe(): string { return $this->basePath().'/danfe'; }
    public function pathEventos(): string { return $this->basePath().'/eventos'; }
    public function pathCancelamentos(): string { return $this->basePath().'/eventos/cancelamento'; }
    public function pathCce(): string { return $this->basePath().'/eventos/carta-correcao'; }
    public function pathInutilizacoes(): string { return $this->basePath().'/eventos/inutilizacao'; }

    public function ensureDirectories(): void
    {
        $disk = $this->storageDisk();
        foreach ([
            $this->pathXml(),
            $this->pathDanfe(),
            $this->pathEventos(),
            $this->pathCancelamentos(),
            $this->pathCce(),
            $this->pathInutilizacoes(),
        ] as $dir) {
            Storage::disk($disk)->makeDirectory($dir);
        }
    }
}


