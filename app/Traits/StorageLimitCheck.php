<?php

namespace App\Traits;

use App\Models\TenantStorageUsage;
use Illuminate\Support\Facades\Cache;

trait StorageLimitCheck
{
    /**
     * Verifica se pode adicionar dados/arquivos (versão otimizada com cache)
     * 
     * @param string $type 'data' ou 'files'
     * @param int $sizeBytes Tamanho estimado em bytes
     * @return bool
     */
    protected function checkStorageLimit(string $type, int $sizeBytes): bool
    {
        try {
            $tenant = auth()->user()->tenant;
            if (!$tenant) {
                return true; // Sem tenant = sem limite
            }
            
            // Buscar usage com cache (5 minutos) - otimização de performance
            $cacheKey = "tenant_storage_{$tenant->id}";
            $usage = Cache::remember($cacheKey, 300, function() use ($tenant) {
                return $tenant->storageUsage;
            });
            
            // Se não tem registro = sem limite configurado
            if (!$usage) {
                return true;
            }
            
            // Verificação matemática simples (sem queries adicionais)
            if ($type === 'data') {
                $limitMb = $usage->total_data_limit_mb;
                if ($limitMb === -1) {
                    return true; // Ilimitado
                }
                
                $limitBytes = $limitMb * 1024 * 1024;
                $newTotal = $usage->data_size_bytes + $sizeBytes;
                return $newTotal <= $limitBytes;
            } else {
                // files
                $limitMb = $usage->total_files_limit_mb;
                if ($limitMb === -1) {
                    return true; // Ilimitado
                }
                
                $limitBytes = $limitMb * 1024 * 1024;
                $newTotal = $usage->files_size_bytes + $sizeBytes;
                return $newTotal <= $limitBytes;
            }
        } catch (\Exception $e) {
            \Log::error('Storage limit check failed', [
                'error' => $e->getMessage(),
                'tenant_id' => auth()->user()->tenant_id ?? null
            ]);
            // Em caso de erro, permitir operação (fail-open para não bloquear usuário)
            return true;
        }
    }
    
    /**
     * Invalida o cache de storage do tenant
     */
    protected function invalidateStorageCache(): void
    {
        $tenant = auth()->user()->tenant;
        if ($tenant) {
            Cache::forget("tenant_storage_{$tenant->id}");
        }
    }
    
    /**
     * Retorna mensagem de erro amigável para limite atingido
     */
    protected function getStorageLimitErrorMessage(string $type): string
    {
        $typeLabel = $type === 'data' ? 'dados' : 'arquivos';
        $upgradeUrl = route('plans.upgrade');
        $storageUrl = route('storage.index');
        
        return "Limite de armazenamento de {$typeLabel} atingido. <a href='{$upgradeUrl}' class='text-blue-600 hover:text-blue-800 underline'>Faça upgrade</a> ou <a href='{$storageUrl}' class='text-blue-600 hover:text-blue-800 underline'>compre espaço adicional</a>.";
    }
}

