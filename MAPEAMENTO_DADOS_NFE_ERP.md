# Mapeamento de Dados para Emissão de NF-e - ERP QFiscal

## 1. DADOS DO EMISSOR (Emitente)

### Fonte: Tabela `tenant_emitters`
```sql
-- Configuração específica do emissor NFe por tenant
SELECT * FROM tenant_emitters WHERE tenant_id = ?
```

**Campos disponíveis:**
- `cnpj` - CNPJ da empresa emitente
- `ie` - Inscrição Estadual
- `razao_social` - Razão social completa
- `nome_fantasia` - Nome fantasia
- `telefone` - Telefone de contato
- `email` - Email da empresa
- `logradouro` - Endereço (rua/avenida)
- `numero` - Número do endereço
- `complemento` - Complemento do endereço
- `bairro` - Bairro
- `cep` - CEP
- `cidade` - Cidade
- `uf` - Estado (UF)
- `codigo_municipio` - Código IBGE do município
- `certificado_caminho` - Caminho do certificado digital (.pfx)
- `certificado_senha` - Senha do certificado (criptografada)
- `certificado_validade` - Data de validade do certificado
- `regime_tributario` - Regime tributário (do TenantTaxConfig)
- `modelo_nfe` - Modelo da NF-e (55)
- `serie_nfe` - Série da NF-e
- `numero_atual_nfe` - Próximo número a ser usado
- `percentual_credito_icms` - % de crédito ICMS
- `storage_disk` - Disco de armazenamento (local/s3)
- `storage_path` - Caminho base para arquivos

### Complemento: Tabela `tenant_tax_configs`
```sql
-- Configuração tributária do tenant
SELECT * FROM tenant_tax_configs WHERE tenant_id = ?
```

**Campos complementares:**
- `regime_tributario` - simples_nacional, lucro_presumido, lucro_real
- `cnae_principal` - CNAE principal da empresa
- `anexo_simples` - Anexo do Simples Nacional (I, II, III, IV, V)
- `aliquota_simples_nacional` - Alíquota do Simples Nacional

### Fallback: Tabela `tenants` (dados do perfil)
```sql
-- Dados básicos da empresa (fallback se emissor não configurado)
SELECT * FROM tenants WHERE id = ?
```

## 2. DADOS DO DESTINATÁRIO (Cliente)

### Fonte: Tabela `clients`
```sql
-- Cliente do pedido/ordem de serviço
SELECT * FROM clients WHERE id = ? AND tenant_id = ?
```

**Campos disponíveis:**
- `name` - Nome/Razão social do cliente
- `cpf_cnpj` - CPF ou CNPJ (sem formatação)
- `ie_rg` - Inscrição Estadual ou RG
- `type` - Tipo: 'person' (CPF) ou 'company' (CNPJ)
- `email` - Email do cliente
- `phone` - Telefone (sem formatação)
- `address` - Logradouro
- `number` - Número
- `complement` - Complemento
- `neighborhood` - Bairro
- `city` - Cidade
- `state` - UF
- `zip_code` - CEP (sem formatação)
- `codigo_ibge` - Código IBGE do município
- `consumidor_final` - Se é consumidor final (boolean)

## 3. ITENS DA NOTA FISCAL

### Fonte: Tabela `order_items` + `products`
```sql
-- Itens do pedido com dados do produto
SELECT 
    oi.*, 
    p.name, p.sku, p.ean, p.ncm, p.cest, p.cfop, p.origin,
    p.csosn, p.cst_icms, p.cst_pis, p.cst_cofins,
    p.aliquota_icms, p.aliquota_pis, p.aliquota_cofins,
    p.unit, p.fiscal_observations, p.fiscal_info
FROM order_items oi
LEFT JOIN products p ON p.id = oi.product_id
WHERE oi.order_id = ? AND oi.tenant_id = ?
```

**Campos do item:**
- `name` - Nome/descrição do produto
- `quantity` - Quantidade
- `unit` - Unidade (UN, KG, etc.)
- `unit_price` - Valor unitário
- `discount_value` - Desconto por item
- `addition_value` - Acréscimo por item
- `line_total` - Total da linha

**Campos fiscais do produto:**
- `sku` - Código interno
- `ean` - Código de barras/GTIN
- `ncm` - Código NCM (8 dígitos)
- `cest` - Código CEST (quando aplicável)
- `cfop` - CFOP padrão do produto
- `origin` - Origem da mercadoria (0-8)
- `csosn` - CSOSN (Simples Nacional)
- `cst_icms` - CST ICMS (Regime Normal)
- `cst_pis` - CST PIS
- `cst_cofins` - CST COFINS
- `aliquota_icms` - Alíquota ICMS do produto
- `aliquota_pis` - Alíquota PIS do produto
- `aliquota_cofins` - Alíquota COFINS do produto

## 4. IMPOSTOS E TRIBUTAÇÃO

### Fonte: Tabela `tax_rates`
```sql
-- Regras tributárias por NCM/CFOP
SELECT * FROM tax_rates 
WHERE tenant_id = ? 
  AND tipo_nota = 'produto'
  AND (ncm = ? OR cfop = ?)
  AND ativo = 1
ORDER BY CASE 
  WHEN ncm = ? AND cfop = ? THEN 0 
  WHEN ncm = ? THEN 1 
  WHEN cfop = ? THEN 2 
  ELSE 3 
END
LIMIT 1
```

**Alíquotas disponíveis:**
- `icms_aliquota` - Alíquota ICMS
- `icms_reducao_bc` - Redução da base de cálculo ICMS
- `pis_aliquota` - Alíquota PIS
- `cofins_aliquota` - Alíquota COFINS
- `iss_aliquota` - Alíquota ISS (serviços)
- `icmsst_modalidade` - Modalidade ICMS-ST
- `icmsst_mva` - MVA para ICMS-ST
- `icmsst_aliquota` - Alíquota ICMS-ST
- `icmsst_reducao_bc` - Redução BC ICMS-ST

### Créditos Fiscais: Tabela `tax_credits`
```sql
-- Créditos de ICMS disponíveis por produto
SELECT * FROM tax_credits 
WHERE tenant_id = ? 
  AND product_id = ?
  AND status = 'active'
  AND fully_used = 0
```

**Campos de crédito:**
- `base_calculo_icms` - Base de cálculo original
- `valor_icms` - Valor total do crédito
- `quantity` - Quantidade original
- `quantity_used` - Quantidade já utilizada
- `valor_icms_used` - Valor já utilizado

## 5. TRANSPORTE E FRETE

### Fonte: Tabela `orders` + `carriers`
```sql
-- Dados de frete do pedido
SELECT 
    o.freight_mode, o.freight_payer, o.freight_cost, o.freight_obs,
    o.volume_qtd, o.volume_especie, o.peso_bruto, o.peso_liquido,
    o.valor_seguro, o.outras_despesas,
    c.name as carrier_name, c.cnpj as carrier_cnpj, c.ie as carrier_ie,
    c.street, c.number, c.complement, c.district, c.city, c.state, c.zip_code,
    c.vehicle_plate, c.vehicle_state, c.rntc
FROM orders o
LEFT JOIN carriers c ON c.id = o.carrier_id
WHERE o.id = ? AND o.tenant_id = ?
```

**Modalidades de frete (`freight_mode`):**
- `0` - Por conta do emitente (CIF)
- `1` - Por conta do destinatário (FOB)
- `2` - Por conta de terceiros
- `9` - Sem frete

**Dados de transporte:**
- `freight_payer` - company/buyer
- `freight_cost` - Valor do frete
- `volume_qtd` - Quantidade de volumes
- `volume_especie` - Espécie dos volumes
- `peso_bruto` - Peso bruto total
- `peso_liquido` - Peso líquido total
- `valor_seguro` - Valor do seguro
- `outras_despesas` - Outras despesas acessórias

## 6. FORMAS DE PAGAMENTO

### Fonte: Tabela `receivables`
```sql
-- Parcelas/pagamentos do pedido
SELECT * FROM receivables 
WHERE tenant_id = ? 
  AND order_id = ?
ORDER BY due_date
```

**Mapeamento de métodos de pagamento:**
```php
$tPagMap = [
    'cash' => '01',        // Dinheiro
    'pix' => '17',         // PIX
    'card' => '03',        // Cartão de Crédito
    'debit' => '04',       // Cartão de Débito
    'boleto' => '15',      // Boleto Bancário
    'transfer' => '05',    // Transferência
    'check' => '02',       // Cheque
    'other' => '99',       // Outros
];
```

**Campos do recebível:**
- `amount` - Valor da parcela
- `due_date` - Data de vencimento
- `payment_method` - Método de pagamento
- `status` - Status: open, paid, partial, canceled

## 7. INFORMAÇÕES ADICIONAIS

### Fonte: Tabela `orders`
```sql
-- Observações e informações complementares
SELECT additional_info, fiscal_info FROM orders WHERE id = ?
```

**Campos disponíveis:**
- `additional_info` - Informações complementares (infCpl)
- `fiscal_info` - Informações ao fisco (infAdFisco)

## 8. ESTRUTURA DO PAYLOAD JSON PARA DELPHI

### Payload Completo
```json
{
  "tipo_operacao": "venda",
  "ambiente": "homologacao",
  "modelo": "55",
  "serie": "1",
  "numero": 123,
  
  "emitente": {
    "cnpj": "12345678000123",
    "ie": "123456789",
    "razao_social": "Empresa Exemplo LTDA",
    "nome_fantasia": "Empresa Exemplo",
    "logradouro": "Rua das Flores, 123",
    "numero": "123",
    "complemento": "Sala 1",
    "bairro": "Centro",
    "cep": "12345678",
    "cidade": "São Paulo",
    "uf": "SP",
    "codigo_municipio": "3550308",
    "telefone": "11999999999",
    "email": "contato@empresa.com.br",
    "regime_tributario": "simples_nacional",
    "certificado_caminho": "/path/to/cert.pfx",
    "certificado_senha": "senha123"
  },
  
  "destinatario": {
    "tipo_pessoa": "juridica",
    "cnpj": "98765432000111",
    "ie": "987654321",
    "razao_social": "Cliente Exemplo LTDA",
    "logradouro": "Av. Principal, 456",
    "numero": "456",
    "bairro": "Jardim",
    "cep": "87654321",
    "cidade": "Rio de Janeiro",
    "uf": "RJ",
    "codigo_municipio": "3304557",
    "telefone": "21888888888",
    "email": "cliente@exemplo.com.br",
    "consumidor_final": false
  },
  
  "produtos": [
    {
      "id": 1,
      "nome": "Produto Exemplo",
      "codigo_interno": "PROD001",
      "codigo_barras": "7891234567890",
      "ncm": "12345678",
      "cest": "1234567",
      "origem": 0,
      "unidade": "UN",
      "quantidade": 2.0,
      "valor_unitario": 100.00,
      "valor_total": 200.00,
      "vDesc": 10.00,
      "vFrete": 5.00,
      "vSeg": 2.00,
      "vOutro": 3.00,
      "cfop": "5102",
      "cst_icms": "00",
      "csosn": "102",
      "aliquota_icms": 18.00,
      "base_calculo_icms": 200.00,
      "valor_icms": 36.00,
      "cst_pis": "01",
      "aliquota_pis": 1.65,
      "base_calculo_pis": 200.00,
      "valor_pis": 3.30,
      "cst_cofins": "01",
      "aliquota_cofins": 7.60,
      "base_calculo_cofins": 200.00,
      "valor_cofins": 15.20
    }
  ],
  
  "transporte": {
    "modalidade_frete": 0,
    "transportadora": {
      "cnpj": "11111111000111",
      "ie": "111111111",
      "razao_social": "Transportadora ABC LTDA",
      "logradouro": "Rua do Transporte, 789",
      "cidade": "São Paulo",
      "uf": "SP"
    },
    "veiculo": {
      "placa": "ABC1234",
      "uf": "SP",
      "rntc": "123456789"
    },
    "volumes": [
      {
        "quantidade": 1,
        "especie": "Caixa",
        "peso_liquido": 10.5,
        "peso_bruto": 12.0
      }
    ],
    "valor_frete": 15.00,
    "valor_seguro": 5.00,
    "outras_despesas": 2.00
  },
  
  "pagamentos": [
    {
      "forma_pagamento": "01",
      "valor": 100.00,
      "vencimento": "2024-01-15"
    },
    {
      "forma_pagamento": "15",
      "valor": 100.00,
      "vencimento": "2024-02-15"
    }
  ],
  
  "observacoes": {
    "inf_complementar": "Pedido 123 - Observações gerais",
    "inf_fisco": "Informações específicas ao fisco"
  },
  
  "totais": {
    "valor_produtos": 200.00,
    "valor_desconto": 10.00,
    "valor_frete": 15.00,
    "valor_seguro": 5.00,
    "outras_despesas": 2.00,
    "valor_total": 212.00,
    "valor_icms": 36.00,
    "valor_pis": 3.30,
    "valor_cofins": 15.20
  }
}
```

## 9. QUERIES PRINCIPAIS PARA MONTAGEM DO PAYLOAD

### Query Principal - Dados do Pedido Completo
```sql
SELECT 
    -- Pedido
    o.id, o.number, o.title, o.total_amount, o.discount_total, o.addition_total,
    o.freight_mode, o.freight_payer, o.freight_cost, o.freight_obs,
    o.volume_qtd, o.volume_especie, o.peso_bruto, o.peso_liquido,
    o.valor_seguro, o.outras_despesas, o.additional_info, o.fiscal_info,
    
    -- Cliente
    c.name as client_name, c.cpf_cnpj, c.ie_rg, c.type as client_type,
    c.email as client_email, c.phone as client_phone, c.address as client_address,
    c.number as client_number, c.complement as client_complement,
    c.neighborhood as client_neighborhood, c.city as client_city,
    c.state as client_state, c.zip_code as client_zip, c.codigo_ibge as client_ibge,
    c.consumidor_final,
    
    -- Transportadora
    carr.name as carrier_name, carr.cnpj as carrier_cnpj, carr.ie as carrier_ie,
    carr.street as carrier_street, carr.number as carrier_number,
    carr.city as carrier_city, carr.state as carrier_state,
    carr.vehicle_plate, carr.vehicle_state, carr.rntc,
    
    -- Emissor
    e.cnpj as emit_cnpj, e.ie as emit_ie, e.razao_social, e.nome_fantasia,
    e.logradouro as emit_logradouro, e.numero as emit_numero,
    e.complemento as emit_complemento, e.bairro as emit_bairro,
    e.cep as emit_cep, e.cidade as emit_cidade, e.uf as emit_uf,
    e.codigo_municipio as emit_codigo_municipio, e.telefone as emit_telefone,
    e.email as emit_email, e.certificado_caminho, e.certificado_senha,
    e.modelo_nfe, e.serie_nfe, e.numero_atual_nfe,
    
    -- Config Tributária
    tc.regime_tributario, tc.cnae_principal, tc.anexo_simples
    
FROM orders o
INNER JOIN clients c ON c.id = o.client_id
LEFT JOIN carriers carr ON carr.id = o.carrier_id
INNER JOIN tenant_emitters e ON e.tenant_id = o.tenant_id
LEFT JOIN tenant_tax_configs tc ON tc.tenant_id = o.tenant_id
WHERE o.id = ? AND o.tenant_id = ?
```

### Query Itens com Produtos
```sql
SELECT 
    oi.*,
    p.name, p.sku, p.ean, p.ncm, p.cest, p.cfop, p.origin,
    p.csosn, p.cst_icms, p.cst_pis, p.cst_cofins,
    p.aliquota_icms, p.aliquota_pis, p.aliquota_cofins,
    p.unit, p.fiscal_observations, p.fiscal_info
FROM order_items oi
LEFT JOIN products p ON p.id = oi.product_id
WHERE oi.order_id = ? AND oi.tenant_id = ?
ORDER BY oi.id
```

### Query Pagamentos
```sql
SELECT 
    payment_method, 
    SUM(amount) as total_amount,
    MIN(due_date) as first_due_date,
    COUNT(*) as installments
FROM receivables 
WHERE tenant_id = ? AND order_id = ?
GROUP BY payment_method
ORDER BY first_due_date
```

## 10. OBSERVAÇÕES IMPORTANTES

### Prioridades de Configuração Fiscal:
1. **Produto individual** - CST, alíquotas específicas do produto
2. **TaxRate por NCM/CFOP** - Regras tributárias configuradas
3. **TenantTaxConfig** - Configuração geral do tenant
4. **Valores padrão** - Fallbacks do sistema

### Validações Necessárias:
- Emissor deve estar configurado (`tenant_emitters`)
- Certificado digital válido e acessível
- Cliente com dados mínimos (nome, documento, endereço)
- Produtos com NCM obrigatório
- CFOP válido para a operação
- Regime tributário definido

### Campos Obrigatórios NFe:
- **Emitente**: CNPJ, IE, razão social, endereço completo
- **Destinatário**: documento (CPF/CNPJ), nome, endereço
- **Produtos**: descrição, NCM, quantidade, valor, CFOP, CST
- **Totais**: valor produtos, impostos, valor total

### Arquivos Gerados:
- **XML**: `{storage_path}/xml/{tenant_id}/NFe{numero}.xml`
- **DANFE**: `{storage_path}/danfe/{tenant_id}/NFe{numero}.pdf`
- **Eventos**: `{storage_path}/eventos/{tenant_id}/`
