# Instruções para Montagem de Payload NFe - Emissor Delphi

## 1. ESTRUTURA BASE DO PAYLOAD

### Cabeçalho da Requisição
```json
{
  "tipo_operacao": "venda",
  "ambiente": "homologacao",  // ou "producao"
  "modelo": "55",
  "serie": "1",
  "numero": 123
}
```

## 2. DADOS DO EMITENTE

### Como Buscar no Laravel:
```php
// 1. Buscar configuração do emissor
$emitter = TenantEmitter::where('tenant_id', $tenantId)->first();

// 2. Buscar configuração tributária
$taxConfig = TenantTaxConfig::where('tenant_id', $tenantId)->first();

// 3. Fallback para dados do tenant se emissor não configurado
$tenant = Tenant::find($tenantId);
```

### Estrutura JSON do Emitente:
```json
{
  "emitente": {
    "cnpj": "12345678000123",
    "ie": "123456789",
    "razao_social": "Empresa Exemplo LTDA",
    "nome_fantasia": "Empresa Exemplo",
    "logradouro": "Rua das Flores",
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
    "certificado_senha": "senha_descriptografada"
  }
}
```

### Código Laravel para Montar Emitente:
```php
function buildEmitente($tenantId) {
    $emitter = TenantEmitter::where('tenant_id', $tenantId)->first();
    $taxConfig = TenantTaxConfig::where('tenant_id', $tenantId)->first();
    
    if (!$emitter) {
        throw new Exception('Emissor não configurado');
    }
    
    return [
        'cnpj' => preg_replace('/\D/', '', $emitter->cnpj),
        'ie' => $emitter->ie,
        'razao_social' => $emitter->razao_social,
        'nome_fantasia' => $emitter->nome_fantasia,
        'logradouro' => $emitter->logradouro,
        'numero' => $emitter->numero,
        'complemento' => $emitter->complemento,
        'bairro' => $emitter->bairro,
        'cep' => preg_replace('/\D/', '', $emitter->cep),
        'cidade' => $emitter->cidade,
        'uf' => $emitter->uf,
        'codigo_municipio' => $emitter->codigo_municipio,
        'telefone' => preg_replace('/\D/', '', $emitter->telefone),
        'email' => $emitter->email,
        'regime_tributario' => $taxConfig->regime_tributario ?? 'simples_nacional',
        'certificado_caminho' => $emitter->certificado_caminho,
        'certificado_senha' => decrypt($emitter->certificado_senha),
    ];
}
```

## 3. DADOS DO DESTINATÁRIO

### Como Buscar no Laravel:
```php
$client = Client::find($clientId);
```

### Estrutura JSON do Destinatário:
```json
{
  "destinatario": {
    "tipo_pessoa": "juridica",  // ou "fisica"
    "cnpj": "98765432000111",   // ou "cpf"
    "ie": "987654321",          // ou "rg"
    "razao_social": "Cliente Exemplo LTDA",
    "logradouro": "Av. Principal",
    "numero": "456",
    "complemento": "Andar 2",
    "bairro": "Jardim",
    "cep": "87654321",
    "cidade": "Rio de Janeiro",
    "uf": "RJ",
    "codigo_municipio": "3304557",
    "telefone": "21888888888",
    "email": "cliente@exemplo.com.br",
    "consumidor_final": false
  }
}
```

### Código Laravel para Montar Destinatário:
```php
function buildDestinatario($client) {
    $isPF = $client->type === 'person';
    $document = preg_replace('/\D/', '', $client->cpf_cnpj);
    
    return [
        'tipo_pessoa' => $isPF ? 'fisica' : 'juridica',
        $isPF ? 'cpf' : 'cnpj' => $document,
        $isPF ? 'rg' : 'ie' => $client->ie_rg,
        'razao_social' => $client->name,
        'logradouro' => $client->address,
        'numero' => $client->number,
        'complemento' => $client->complement,
        'bairro' => $client->neighborhood,
        'cep' => preg_replace('/\D/', '', $client->zip_code),
        'cidade' => $client->city,
        'uf' => $client->state,
        'codigo_municipio' => $client->codigo_ibge,
        'telefone' => preg_replace('/\D/', '', $client->phone),
        'email' => $client->email,
        'consumidor_final' => (bool) $client->consumidor_final,
    ];
}
```

## 4. PRODUTOS/ITENS COM IMPOSTOS

### Como Buscar no Laravel:
```php
$items = OrderItem::with('product')
    ->where('order_id', $orderId)
    ->where('tenant_id', $tenantId)
    ->get();

// Para cada item, buscar regra tributária
$taxRate = TaxRate::where('tenant_id', $tenantId)
    ->where('tipo_nota', 'produto')
    ->where(function($q) use ($product) {
        $q->where('ncm', $product->ncm)
          ->orWhere('cfop', $product->cfop);
    })
    ->where('ativo', 1)
    ->first();
```

### Estrutura JSON dos Produtos:
```json
{
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
      
      // ICMS
      "cst_icms": "00",
      "csosn": "102",
      "aliquota_icms": 18.00,
      "base_calculo_icms": 200.00,
      "valor_icms": 36.00,
      "reducao_bc_icms": 0.00,
      
      // PIS
      "cst_pis": "01",
      "aliquota_pis": 1.65,
      "base_calculo_pis": 200.00,
      "valor_pis": 3.30,
      
      // COFINS
      "cst_cofins": "01",
      "aliquota_cofins": 7.60,
      "base_calculo_cofins": 200.00,
      "valor_cofins": 15.20,
      
      // ICMS-ST (quando aplicável)
      "modalidade_icmsst": 0,
      "mva_icmsst": 30.00,
      "aliquota_icmsst": 18.00,
      "base_calculo_icmsst": 260.00,
      "valor_icmsst": 46.80
    }
  ]
}
```

### Código Laravel para Montar Produtos:
```php
function buildProdutos($order, $tenantId) {
    $items = $order->items()->with('product')->get();
    $produtos = [];
    
    foreach ($items as $item) {
        $product = $item->product;
        if (!$product) continue;
        
        // Buscar regra tributária
        $taxRate = TaxRate::where('tenant_id', $tenantId)
            ->where('tipo_nota', 'produto')
            ->where(function($q) use ($product) {
                $q->where('ncm', $product->ncm)
                  ->orWhere('cfop', $product->cfop);
            })
            ->where('ativo', 1)
            ->orderByRaw("CASE WHEN ncm = ? AND cfop = ? THEN 0 WHEN ncm = ? THEN 1 WHEN cfop = ? THEN 2 ELSE 3 END", 
                [$product->ncm, $product->cfop, $product->ncm, $product->cfop])
            ->first();
        
        // Valores base
        $vProd = (float) ($item->quantity * $item->unit_price);
        $vDesc = (float) ($item->discount_value ?? 0);
        $vOutro = (float) ($item->addition_value ?? 0);
        
        // Rateio de frete/seguro/outras despesas (implementar lógica de rateio)
        $vFrete = 0; // TODO: implementar rateio proporcional
        $vSeg = 0;   // TODO: implementar rateio proporcional
        
        // Base de cálculo ICMS
        $baseIcms = max($vProd - $vDesc, 0) + $vFrete + $vSeg + $vOutro;
        
        // Alíquotas (produto > regra tributária > padrão)
        $aliqIcms = (float) ($product->aliquota_icms ?? $taxRate->icms_aliquota ?? 0);
        $aliqPis = (float) ($product->aliquota_pis ?? $taxRate->pis_aliquota ?? 0);
        $aliqCofins = (float) ($product->aliquota_cofins ?? $taxRate->cofins_aliquota ?? 0);
        
        // Calcular impostos
        $valorIcms = $baseIcms * ($aliqIcms / 100);
        $valorPis = $baseIcms * ($aliqPis / 100);
        $valorCofins = $baseIcms * ($aliqCofins / 100);
        
        // Determinar CST baseado no regime tributário
        $taxConfig = TenantTaxConfig::where('tenant_id', $tenantId)->first();
        $isSimples = $taxConfig && $taxConfig->regime_tributario === 'simples_nacional';
        
        $produtos[] = [
            'id' => $product->id,
            'nome' => $product->name,
            'codigo_interno' => $product->sku,
            'codigo_barras' => $product->ean,
            'ncm' => $product->ncm,
            'cest' => $product->cest,
            'origem' => (int) ($product->origin ?? 0),
            'unidade' => $product->unit ?? 'UN',
            'quantidade' => (float) $item->quantity,
            'valor_unitario' => (float) $item->unit_price,
            'valor_total' => $vProd,
            'vDesc' => $vDesc,
            'vFrete' => $vFrete,
            'vSeg' => $vSeg,
            'vOutro' => $vOutro,
            'cfop' => $product->cfop ?: '5102',
            
            // ICMS
            'cst_icms' => $isSimples ? null : $product->cst_icms,
            'csosn' => $isSimples ? $product->csosn : null,
            'aliquota_icms' => $aliqIcms,
            'base_calculo_icms' => $baseIcms,
            'valor_icms' => $valorIcms,
            
            // PIS/COFINS
            'cst_pis' => $product->cst_pis,
            'aliquota_pis' => $aliqPis,
            'base_calculo_pis' => $baseIcms,
            'valor_pis' => $valorPis,
            'cst_cofins' => $product->cst_cofins,
            'aliquota_cofins' => $aliqCofins,
            'base_calculo_cofins' => $baseIcms,
            'valor_cofins' => $valorCofins,
        ];
    }
    
    return $produtos;
}
```

## 5. TRANSPORTE

### Como Buscar no Laravel:
```php
$order = Order::with('carrier')->find($orderId);
```

### Estrutura JSON do Transporte:
```json
{
  "transporte": {
    "modalidade_frete": 0,  // 0=CIF, 1=FOB, 2=Terceiros, 9=Sem frete
    "transportadora": {
      "cnpj": "11111111000111",
      "ie": "111111111",
      "razao_social": "Transportadora ABC LTDA",
      "logradouro": "Rua do Transporte",
      "numero": "789",
      "bairro": "Industrial",
      "cidade": "São Paulo",
      "uf": "SP",
      "cep": "01234567"
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
  }
}
```

### Código Laravel para Montar Transporte:
```php
function buildTransporte($order) {
    $transport = [
        'modalidade_frete' => (int) $order->freight_mode,
        'valor_frete' => (float) ($order->freight_cost ?? 0),
        'valor_seguro' => (float) ($order->valor_seguro ?? 0),
        'outras_despesas' => (float) ($order->outras_despesas ?? 0),
    ];
    
    // Transportadora (se informada)
    if ($order->carrier) {
        $transport['transportadora'] = [
            'cnpj' => preg_replace('/\D/', '', $order->carrier->cnpj),
            'ie' => $order->carrier->ie,
            'razao_social' => $order->carrier->name,
            'logradouro' => $order->carrier->street,
            'numero' => $order->carrier->number,
            'complemento' => $order->carrier->complement,
            'bairro' => $order->carrier->district,
            'cidade' => $order->carrier->city,
            'uf' => $order->carrier->state,
            'cep' => preg_replace('/\D/', '', $order->carrier->zip_code),
        ];
        
        // Veículo (se informado)
        if ($order->carrier->vehicle_plate) {
            $transport['veiculo'] = [
                'placa' => $order->carrier->vehicle_plate,
                'uf' => $order->carrier->vehicle_state,
                'rntc' => $order->carrier->rntc,
            ];
        }
    }
    
    // Volumes (se informados)
    if ($order->volume_qtd) {
        $transport['volumes'] = [[
            'quantidade' => (int) $order->volume_qtd,
            'especie' => $order->volume_especie ?? 'Volume',
            'peso_liquido' => (float) ($order->peso_liquido ?? 0),
            'peso_bruto' => (float) ($order->peso_bruto ?? 0),
        ]];
    }
    
    return $transport;
}
```

## 6. PAGAMENTOS

### Como Buscar no Laravel:
```php
$receivables = Receivable::where('order_id', $orderId)
    ->where('tenant_id', $tenantId)
    ->orderBy('due_date')
    ->get();
```

### Mapeamento de Formas de Pagamento:
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

### Estrutura JSON dos Pagamentos:
```json
{
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
  ]
}
```

### Código Laravel para Montar Pagamentos:
```php
function buildPagamentos($orderId, $tenantId) {
    $tPagMap = [
        'cash' => '01', 'pix' => '17', 'card' => '03', 'debit' => '04',
        'boleto' => '15', 'transfer' => '05', 'check' => '02', 'other' => '99',
    ];
    
    $receivables = Receivable::where('order_id', $orderId)
        ->where('tenant_id', $tenantId)
        ->orderBy('due_date')
        ->get();
    
    $pagamentos = [];
    foreach ($receivables as $rec) {
        $tPag = $tPagMap[$rec->payment_method] ?? '99';
        
        $pagamentos[] = [
            'forma_pagamento' => $tPag,
            'valor' => (float) $rec->amount,
            'vencimento' => $rec->due_date->format('Y-m-d'),
        ];
    }
    
    return $pagamentos;
}
```

## 7. OBSERVAÇÕES E TOTAIS

### Estrutura JSON:
```json
{
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

### Código Laravel:
```php
function buildObservacoes($order) {
    return [
        'inf_complementar' => $order->additional_info,
        'inf_fisco' => $order->fiscal_info,
    ];
}

function buildTotais($produtos, $order) {
    $valorProdutos = array_sum(array_column($produtos, 'valor_total'));
    $valorDesconto = array_sum(array_column($produtos, 'vDesc'));
    $valorIcms = array_sum(array_column($produtos, 'valor_icms'));
    $valorPis = array_sum(array_column($produtos, 'valor_pis'));
    $valorCofins = array_sum(array_column($produtos, 'valor_cofins'));
    
    return [
        'valor_produtos' => $valorProdutos,
        'valor_desconto' => $valorDesconto,
        'valor_frete' => (float) ($order->freight_cost ?? 0),
        'valor_seguro' => (float) ($order->valor_seguro ?? 0),
        'outras_despesas' => (float) ($order->outras_despesas ?? 0),
        'valor_total' => (float) $order->total_amount,
        'valor_icms' => $valorIcms,
        'valor_pis' => $valorPis,
        'valor_cofins' => $valorCofins,
    ];
}
```

## 8. FUNÇÃO PRINCIPAL PARA MONTAR PAYLOAD COMPLETO

```php
function buildNFePayload($orderId, $tenantId) {
    $order = Order::with(['client', 'carrier', 'items.product'])
        ->where('id', $orderId)
        ->where('tenant_id', $tenantId)
        ->first();
    
    if (!$order) {
        throw new Exception('Pedido não encontrado');
    }
    
    $emitter = TenantEmitter::where('tenant_id', $tenantId)->first();
    if (!$emitter) {
        throw new Exception('Emissor não configurado');
    }
    
    // Montar payload completo
    $payload = [
        'tipo_operacao' => 'venda',
        'ambiente' => config('app.env') === 'production' ? 'producao' : 'homologacao',
        'modelo' => $emitter->modelo_nfe ?? '55',
        'serie' => $emitter->serie_nfe ?? '1',
        'numero' => $emitter->numero_atual_nfe,
        
        'emitente' => buildEmitente($tenantId),
        'destinatario' => buildDestinatario($order->client),
        'produtos' => buildProdutos($order, $tenantId),
        'transporte' => buildTransporte($order),
        'pagamentos' => buildPagamentos($orderId, $tenantId),
        'observacoes' => buildObservacoes($order),
    ];
    
    // Calcular totais
    $payload['totais'] = buildTotais($payload['produtos'], $order);
    
    return $payload;
}
```

## 9. VALIDAÇÕES OBRIGATÓRIAS

### Antes de Enviar o Payload:
```php
function validateNFePayload($payload) {
    $errors = [];
    
    // Emitente
    if (empty($payload['emitente']['cnpj'])) $errors[] = 'CNPJ do emitente obrigatório';
    if (empty($payload['emitente']['ie'])) $errors[] = 'IE do emitente obrigatória';
    if (empty($payload['emitente']['certificado_caminho'])) $errors[] = 'Certificado digital obrigatório';
    
    // Destinatário
    if (empty($payload['destinatario']['cnpj']) && empty($payload['destinatario']['cpf'])) {
        $errors[] = 'CPF ou CNPJ do destinatário obrigatório';
    }
    
    // Produtos
    if (empty($payload['produtos'])) $errors[] = 'Pelo menos um produto obrigatório';
    
    foreach ($payload['produtos'] as $i => $prod) {
        if (empty($prod['ncm'])) $errors[] = "NCM obrigatório no produto " . ($i + 1);
        if (empty($prod['cfop'])) $errors[] = "CFOP obrigatório no produto " . ($i + 1);
    }
    
    // Pagamentos
    if (empty($payload['pagamentos'])) $errors[] = 'Pelo menos uma forma de pagamento obrigatória';
    
    if (!empty($errors)) {
        throw new Exception('Erros de validação: ' . implode(', ', $errors));
    }
}
```

## 10. EXEMPLO DE USO COMPLETO

```php
// No controller NFe
public function emitirNfe(Order $order, Request $request) {
    try {
        // Montar payload
        $payload = buildNFePayload($order->id, auth()->user()->tenant_id);
        
        // Validar
        validateNFePayload($payload);
        
        // Enviar para Delphi
        $response = Http::timeout(60)
            ->post(config('services.delphi.url') . '/api/emitir-nfe', $payload);
        
        if ($response->successful()) {
            $result = $response->json();
            
            // Atualizar número da NFe no emissor
            $emitter = TenantEmitter::where('tenant_id', auth()->user()->tenant_id)->first();
            $emitter->numero_atual_nfe = $emitter->numero_atual_nfe + 1;
            $emitter->save();
            
            // Marcar pedido como emitido
            $order->nfe_issued_at = now();
            $order->save();
            
            return response()->json(['success' => true, 'data' => $result]);
        } else {
            return response()->json(['success' => false, 'error' => $response->body()], 400);
        }
        
    } catch (Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
    }
}
```

## 11. CAMPOS IMPORTANTES PARA O DELPHI

### O Delphi precisa processar estes campos principais:

**IDE (Identificação):**
- modelo, serie, numero, ambiente

**Emitente:**
- CNPJ, IE, razão social, endereço completo, certificado

**Destinatário:**
- CPF/CNPJ, nome, endereço completo

**Produtos:**
- NCM, CFOP, quantidade, valores, impostos detalhados

**Transporte:**
- Modalidade, transportadora, volumes, valores

**Pagamento:**
- Formas de pagamento com códigos tPag

**Totais:**
- Valores consolidados para conferência

Este payload completo permite ao Delphi gerar uma NF-e fiscalmente correta e completa!
