<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantStorageUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StorageCalculator
{
    /**
     * Calcula uso de dados do tenant (tamanho em bytes das tabelas)
     * Usa SHOW TABLE STATUS para melhor performance
     */
    public function calculateDataSize(Tenant $tenant): int
    {
        // Tabelas principais que contam como "dados"
        $tables = [
            'clients',
            'products',
            'orders',
            'quotes',
            'service_orders',
            'receivables',
            'payables',
            'invoices',
            'stock_movements',
            'suppliers',
            'categories',
            'service_order_items',
            'order_items',
            'quote_items',
        ];

        $totalBytes = 0;
        foreach ($tables as $table) {
            // Filtro por tenant_id se a tabela tiver esse campo
            // Como todas as tabelas principais têm tenant_id, precisamos calcular apenas os registros desse tenant
            try {
                // Versão otimizada: usar SHOW TABLE STATUS (mais rápido que information_schema)
                $result = DB::select("SHOW TABLE STATUS LIKE '{$table}'");
                if (!empty($result)) {
                    // Como temos multi-tenant na mesma base, precisamos calcular apenas os registros do tenant
                    // Estimativa: tamanho total da tabela * proporção de registros do tenant
                    $tableDataLength = (int) ($result[0]->Data_length ?? 0);
                    $tableIndexLength = (int) ($result[0]->Index_length ?? 0);
                    
                    // Contar registros do tenant vs total
                    $totalRows = (int) ($result[0]->Rows ?? 1);
                    if ($totalRows > 0) {
                        $tenantRows = DB::table($table)->where('tenant_id', $tenant->id)->count();
                        if ($tenantRows > 0) {
                            $proportion = $tenantRows / $totalRows;
                            $totalBytes += (int) (($tableDataLength + $tableIndexLength) * $proportion);
                        }
                    } else {
                        // Se não tem registros, considerar apenas estrutura mínima
                        // Estimativa: 10KB por tabela vazia (estrutura)
                        if ($tableDataLength === 0 && $tableIndexLength === 0) {
                            $totalBytes += 10240; // 10 KB mínimo
                        } else {
                            $totalBytes += ($tableDataLength + $tableIndexLength);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Se a tabela não existir ou der erro, continuar
                \Log::warning("Erro ao calcular tamanho da tabela {$table} para tenant {$tenant->id}: " . $e->getMessage());
                continue;
            }
        }

        return $totalBytes;
    }

    /**
     * Calcula uso de arquivos do tenant (storage/disk)
     */
    public function calculateFilesSize(Tenant $tenant): int
    {
        $totalBytes = 0;
        
        // Diretórios do tenant no storage
        $directories = [
            "tenants/{$tenant->id}/nfe/xml",
            "tenants/{$tenant->id}/nfe/danfe",
            "tenants/{$tenant->id}/products/images",
            "tenants/{$tenant->id}/documents",
            "service_orders", // Verificar anexos de OS que podem estar em subdiretórios
        ];

        foreach ($directories as $dir) {
            try {
                if (Storage::disk('public')->exists($dir)) {
                    $files = Storage::disk('public')->allFiles($dir);
                    
                    // Filtrar apenas arquivos desse tenant se estiver em diretório compartilhado
                    foreach ($files as $file) {
                        // Para service_orders, verificar se o arquivo pertence ao tenant
                        if (strpos($file, "service_orders") !== false) {
                            // Extrair service_order_id do caminho (assumindo formato service_orders/{id}/...)
                            $parts = explode('/', $file);
                            if (isset($parts[1]) && is_numeric($parts[1])) {
                                $serviceOrderId = (int) $parts[1];
                                // Verificar se a OS pertence ao tenant
                                $belongsToTenant = DB::table('service_orders')
                                    ->where('id', $serviceOrderId)
                                    ->where('tenant_id', $tenant->id)
                                    ->exists();
                                
                                if (!$belongsToTenant) {
                                    continue; // Pular arquivos de outros tenants
                                }
                            }
                        }
                        
                        try {
                            $totalBytes += Storage::disk('public')->size($file);
                        } catch (\Exception $e) {
                            // Arquivo pode ter sido deletado, continuar
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Diretório pode não existir, continuar
                \Log::warning("Erro ao calcular tamanho do diretório {$dir} para tenant {$tenant->id}: " . $e->getMessage());
                continue;
            }
        }

        return $totalBytes;
    }

    /**
     * Atualiza uso de storage do tenant
     */
    public function updateTenantUsage(Tenant $tenant): void
    {
        $dataSize = $this->calculateDataSize($tenant);
        $filesSize = $this->calculateFilesSize($tenant);

        TenantStorageUsage::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'data_size_bytes' => $dataSize,
                'files_size_bytes' => $filesSize,
                'last_calculated_at' => now()
            ]
        );
    }

    /**
     * Atualiza uso de todos os tenants (executar via schedule)
     */
    public function updateAllTenants(): void
    {
        Tenant::where('active', true)->each(function ($tenant) {
            $this->updateTenantUsage($tenant);
        });
    }
}

