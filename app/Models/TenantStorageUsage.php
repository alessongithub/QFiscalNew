<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantStorageUsage extends Model
{
    protected $table = 'tenant_storage_usage';

    protected $fillable = [
        'tenant_id',
        'data_size_bytes',
        'files_size_bytes',
        'additional_data_mb',
        'additional_files_mb',
        'last_calculated_at'
    ];

    protected $casts = [
        'data_size_bytes' => 'integer',
        'files_size_bytes' => 'integer',
        'additional_data_mb' => 'integer',
        'additional_files_mb' => 'integer',
        'last_calculated_at' => 'datetime'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Limite total de dados em MB (plano + adicional)
     */
    public function getTotalDataLimitMbAttribute(): int
    {
        $plan = $this->tenant->plan;
        if (!$plan || ($plan->features['storage_data_mb'] ?? -1) === -1) {
            return -1; // Ilimitado
        }
        return ($plan->features['storage_data_mb'] ?? 50) + $this->additional_data_mb;
    }

    /**
     * Limite total de arquivos em MB (plano + adicional)
     */
    public function getTotalFilesLimitMbAttribute(): int
    {
        $plan = $this->tenant->plan;
        if (!$plan || ($plan->features['storage_files_mb'] ?? -1) === -1) {
            return -1; // Ilimitado
        }
        return ($plan->features['storage_files_mb'] ?? 500) + $this->additional_files_mb;
    }

    /**
     * Uso atual de dados em MB
     */
    public function getDataUsageMbAttribute(): float
    {
        return round($this->data_size_bytes / 1024 / 1024, 2);
    }

    /**
     * Uso atual de arquivos em MB
     */
    public function getFilesUsageMbAttribute(): float
    {
        return round($this->files_size_bytes / 1024 / 1024, 2);
    }

    /**
     * Percentual de uso de dados
     */
    public function getDataUsagePercentAttribute(): float
    {
        $limit = $this->total_data_limit_mb;
        if ($limit === -1) return 0;
        return $limit > 0 ? min(100, ($this->data_usage_mb / $limit) * 100) : 0;
    }

    /**
     * Percentual de uso de arquivos
     */
    public function getFilesUsagePercentAttribute(): float
    {
        $limit = $this->total_files_limit_mb;
        if ($limit === -1) return 0;
        return $limit > 0 ? min(100, ($this->files_usage_mb / $limit) * 100) : 0;
    }

    /**
     * Verifica se pode adicionar mais dados
     */
    public function canAddData(int $sizeBytes): bool
    {
        $limit = $this->total_data_limit_mb;
        if ($limit === -1) return true;
        $newTotal = $this->data_size_bytes + $sizeBytes;
        $limitBytes = $limit * 1024 * 1024;
        return $newTotal <= $limitBytes;
    }

    /**
     * Verifica se pode adicionar mais arquivos
     */
    public function canAddFiles(int $sizeBytes): bool
    {
        $limit = $this->total_files_limit_mb;
        if ($limit === -1) return true;
        $newTotal = $this->files_size_bytes + $sizeBytes;
        $limitBytes = $limit * 1024 * 1024;
        return $newTotal <= $limitBytes;
    }
}
