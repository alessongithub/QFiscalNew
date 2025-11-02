# Como Testar o Sistema de Storage Sem Gateway

## 📋 Pré-requisitos

1. Ter tenants ativos no banco
2. Executar migrações: `php artisan migrate`
3. Popular dados iniciais: `php artisan storage:update-usage`

---

## 🧪 Testes Básicos

### 1. Calcular e Atualizar Storage Manualmente

```bash
# Calcular uso de todos os tenants
php artisan storage:update-usage

# Ver logs se necessário
tail -f storage/logs/laravel.log
```

### 2. Verificar Widget no Dashboard

1. Acesse `/dashboard` como usuário de um tenant
2. Veja o widget de armazenamento abaixo dos 4 cards principais
3. Verifique se mostra:
   - Uso de dados (MB / limite)
   - Uso de arquivos (MB / limite)
   - Barras de progresso coloridas
   - Botão "Expandir Espaço" se > 75%

### 3. Verificar Página de Detalhes

1. Acesse `/storage-management` ou clique em "Ver Detalhes"
2. Verifique se mostra:
   - Detalhes completos de dados e arquivos
   - Percentuais de uso
   - Botão "Expandir Espaço"

### 4. Testar Bloqueios em Controllers

#### Teste: Criar Cliente

1. Acesse `/clients/create`
2. Preencha dados
3. **Antes de salvar**: Manualmente reduza o limite do plano do tenant no banco
4. Tente salvar - deve mostrar erro com links para upgrade

**Como simular limite atingido**:
```sql
-- Ver uso atual
SELECT tenant_id, data_size_bytes, additional_data_mb FROM tenant_storage_usage;

-- Reduzir limite do plano temporariamente para teste
UPDATE plans SET features = JSON_SET(features, '$.storage_data_mb', 1) WHERE id = 1;
```

#### Teste: Criar Produto com Imagem

1. Acesse `/products/create`
2. Preencha dados e selecione uma imagem grande
3. Tente salvar - deve verificar tanto dados quanto arquivos

#### Teste: Upload de Anexo em OS

1. Acesse uma OS e clique em "Adicionar Anexo"
2. Selecione arquivo grande
3. Tente fazer upload - deve verificar arquivos antes

---

## 🛠️ Criar Addon Manualmente (Sem Gateway)

Para testar a ativação de addons sem gateway, você pode criar manualmente no banco:

```sql
-- Criar addon pendente
INSERT INTO storage_addons (tenant_id, type, quantity_mb, price, status, created_at, updated_at)
VALUES (1, 'data', 50, 9.90, 'pending', NOW(), NOW());

-- Ativar addon manualmente (simular pagamento aprovado)
UPDATE storage_addons SET status = 'active' WHERE id = 1;

-- Atualizar tenant_storage_usage
UPDATE tenant_storage_usage 
SET additional_data_mb = additional_data_mb + 50 
WHERE tenant_id = 1;
```

Ou via tinker:

```bash
php artisan tinker

$tenant = \App\Models\Tenant::find(1);
$addon = \App\Models\StorageAddon::create([
    'tenant_id' => $tenant->id,
    'type' => 'data',
    'quantity_mb' => 50,
    'price' => 9.90,
    'status' => 'active',
]);

$usage = $tenant->storageUsage;
if (!$usage) {
    $usage = \App\Models\TenantStorageUsage::create([
        'tenant_id' => $tenant->id,
        'data_size_bytes' => 0,
        'files_size_bytes' => 0,
    ]);
}

$usage->additional_data_mb += 50;
$usage->save();

// Verificar limite atualizado
$usage->refresh();
echo "Limite total: " . $usage->total_data_limit_mb . " MB\n";
```

---

## 🔍 Verificar Dados no Banco

```sql
-- Ver todos os tenants e seu uso
SELECT 
    t.id,
    t.name,
    p.name as plan_name,
    tsu.data_size_bytes / 1024 / 1024 as data_mb,
    tsu.files_size_bytes / 1024 / 1024 as files_mb,
    tsu.additional_data_mb,
    tsu.additional_files_mb,
    tsu.last_calculated_at
FROM tenants t
LEFT JOIN plans p ON t.plan_id = p.id
LEFT JOIN tenant_storage_usage tsu ON t.id = tsu.tenant_id
WHERE t.active = 1;

-- Ver addons ativos
SELECT * FROM storage_addons WHERE status = 'active';
```

---

## 📊 Testar Relatórios Admin/Partner

### Admin

1. Acesse `/admin/storage-usage` (precisa criar rota - ver abaixo)
2. Veja lista de todos tenants com consumo
3. Filtre por parceiro se necessário

### Partner

1. Acesse `/partner/storage-usage` (precisa criar rota - ver abaixo)
2. Veja apenas seus tenants com consumo

---

## ⚠️ Problemas Comuns

### Widget não aparece

**Causa**: Tenant não tem `storageUsage` registrado  
**Solução**: 
```bash
php artisan storage:update-usage
```

### Números sempre em 0

**Causa**: Calculadora não encontrou dados/arquivos  
**Solução**: Verifique se há dados no banco e arquivos em `storage/app/public/tenants/{id}/`

### Erro ao verificar storage

**Causa**: Cache desatualizado  
**Solução**: Limpar cache:
```bash
php artisan cache:clear
```

---

## 🎯 Checklist de Testes

- [ ] Widget aparece no dashboard
- [ ] Números de uso estão corretos após `storage:update-usage`
- [ ] Criar cliente bloqueia quando limite atingido
- [ ] Criar produto bloqueia dados quando limite atingido
- [ ] Upload de imagem bloqueia arquivos quando limite atingido
- [ ] Upload de anexo OS bloqueia arquivos quando limite atingido
- [ ] Criar pedido bloqueia quando limite atingido
- [ ] Criar orçamento bloqueia quando limite atingido
- [ ] Upload de logo bloqueia quando limite atingido
- [ ] Addon manual ativa e atualiza limite corretamente
- [ ] Relatório admin mostra todos tenants
- [ ] Relatório partner mostra apenas seus tenants
- [ ] Botão "Expandir Espaço" aparece quando > 75%
- [ ] Página `/storage-management` carrega corretamente

---

## 📝 Notas

- **Limites Ilimitados (-1)**: Tenants com plano Enterprise/Platinum não têm bloqueios
- **Cache**: Storage usa cache de 5 minutos para performance
- **Atualização Diária**: O comando roda automaticamente às 2h (precisa cron configurado em produção)

