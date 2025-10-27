# PROMPT DE CORREÇÃO: Sistema de Pedidos e Emissão de NF-e

## CONTEXTO

Sistema Laravel de emissão de notas fiscais está com 3 problemas críticos que impedem o funcionamento correto:

1. **Valor líquido não aparece corretamente** em `/orders/index`
2. **Desconto geral não é salvo** em `/orders/edit`
3. **XML da NF-e é montado com dados incorretos** (itens zerados, totais errados)

---

## PROBLEMA #1: Valor Líquido Incorreto em /orders/index

### Localização
`resources/views/orders/edit.blade.php` linhas 162-176

### Descrição do Bug
O cálculo do valor líquido está **subtraindo o desconto duas vezes**:

```php
// CÓDIGO ATUAL (ERRADO):
@php
    $net = (float)($o->total_amount ?? 0)
        - (float)($o->discount_total ?? 0)  // ❌ Desconto duplicado!
        + (float)($o->addition_total ?? 0)
        + (float)($o->freight_cost ?? 0)
        + (float)($o->valor_seguro ?? 0)
        + (float)($o->outras_despesas ?? 0);
    if ($net < 0) { $net = 0; }
@endphp
R$ {{ number_format($net, 2, ',', '.') }}
```

### Por que está errado?
No `OrderController.php` linha 248, o campo `total_amount` JÁ contém o valor líquido:

```php
$netTotal = max(0.0, $subtotal - $itemsDiscountSum - $headerDiscount);
$order = Order::create([
    'total_amount'=>$netTotal,  // ✅ JÁ ESTÁ LÍQUIDO
    'discount_total'=>$headerDiscount,  // Guardado separadamente para referência
]);
```

Então **subtrair `discount_total` novamente é duplicar o desconto**.

### ✅ CORREÇÃO NECESSÁRIA

**Arquivo**: `resources/views/orders/index.blade.php` linhas 162-176

```php
// SUBSTITUIR O BLOCO PHP ATUAL POR:
@php
    // total_amount já é líquido (itens - descontos itens - desconto total)
    // Adicionar apenas frete, seguro e outras despesas
    $net = (float)($o->total_amount ?? 0)
        + (float)($o->addition_total ?? 0)
        + (float)($o->freight_cost ?? 0)
        + (float)($o->valor_seguro ?? 0)
        + (float)($o->outras_despesas ?? 0);
    
    if ($net < 0) { $net = 0; }
@endphp
```

### Teste de Validação
Depois da correção, verificar:
1. Criar pedido com desconto total de R$ 100,00
2. Ver o valor na listagem `/orders`
3. Valor deve ser: (soma dos itens) - (desconto itens) - (R$ 100) + (frete)

---

## PROBLEMA #2: Desconto Geral Não é Salvo em /orders/edit

### Localização
- **View**: `resources/views/orders/edit.blade.php` linhas 476 e 1842
- **Controller**: `app/Http/Controllers/OrderController.php` método `update()` linha 353

### Descrição do Bug
Há **dois campos de input** para desconto total na tela de edição:

1. **Linha 476** - Campo inline na seção de itens:
```html
<input form="orderEditMainForm" type="number" step="0.01" min="0" 
       name="discount_total_override" id="discount_total_override_inline"
       value="{{ number_format((float)($order->discount_total ?? 0), 2, '.', '') }}">
```

2. **Linha 1842** - Campo no modal de emissão:
```html
<input type="number" step="0.01" min="0" 
       name="discount_total_override" id="discount_total_override" 
       value="{{ number_format($headerDiscount, 2, '.', '') }}">
```

**O problema**: O formulário `orderEditMainForm` submete para `orders.update`, mas o método `update()` do controller **IGNORA completamente** o campo `discount_total_override`:

```php
// OrderController.php linha 353-419
public function update(Request $request, Order $order)
{
    // ...
    $v = $request->validate([
        'client_id' => 'nullable|exists:clients,id',
        'title' => 'nullable|string|max:255',
        'status' => 'in:open,canceled',
        // ❌ NÃO VALIDA discount_total_override!
    ]);
    
    $payload = [
        'title' => $v['title'] ?? $order->title,
        // ❌ NÃO INCLUI discount_total
    ];
    
    $order->update($payload);
    return back()->with('success','Pedido atualizado.');  // ⚠️ Falso positivo!
}
```

### Por que não funciona?
- Usuário digita desconto → clica "Salvar Alterações"
- Controller ignora o campo `discount_total_override`
- Mostra "Pedido atualizado" (mas desconto não foi salvo)
- **Resultado**: Divergência entre tela e banco de dados

### ✅ CORREÇÃO NECESSÁRIA

**Arquivo**: `app/Http/Controllers/OrderController.php` método `update()` linha 353

```php
public function update(Request $request, Order $order)
{
    abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
    abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
    
    // Bloquear alterações quando o pedido já está finalizado
    $statusNorm = strtolower(trim((string) $order->status));
    if (in_array($statusNorm, ['fulfilled','canceled'], true)) {
        return back()->with('error', 'Pedido finalizado/cancelado não pode ser alterado. Reabra o pedido para editar.');
    }
    
    $clientOnly = $request->boolean('client_only');

    if ($clientOnly) {
        $vv = $request->validate(['client_id' => 'required|exists:clients,id']);
        $order->client_id = (int) $vv['client_id'];
        $order->save();
        return back()->with('success','Cliente do pedido atualizado.');
    }

    // ✅ ADICIONAR VALIDAÇÃO DO DESCONTO
    $rules = [
        'client_id' => 'nullable|exists:clients,id',
        'title' => 'nullable|string|max:255',
        'discount_total_override' => 'nullable|numeric|min:0',  // ✅ NOVO
    ];
    
    if ($request->has('status')) {
        $rules['status'] = 'in:open,canceled';
    }
    
    $messages = [
        'client_id.exists' => 'Cliente inválido.',
        'title.string' => 'O título deve ser um texto válido.',
        'title.max' => 'O título deve ter no máximo 255 caracteres.',
        'status.in' => 'Status inválido. Use Aberto ou Cancelado.',
        'discount_total_override.numeric' => 'Desconto deve ser um número válido.',  // ✅ NOVO
        'discount_total_override.min' => 'Desconto não pode ser negativo.',  // ✅ NOVO
    ];
    
    $attributes = [
        'client_id' => 'cliente',
        'title' => 'título',
        'status' => 'status',
        'discount_total_override' => 'desconto total',  // ✅ NOVO
    ];
    
    $v = $request->validate($rules, $messages, $attributes);

    // Evitar finalizar por alteração direta de status
    if (($v['status'] ?? '') === 'fulfilled') {
        return back()->with('error', 'Para finalizar o pedido use o botão "Finalizar pedido" na seção de frete.');
    }

    $payload = [
        'title' => $v['title'] ?? $order->title,
    ];
    
    if (array_key_exists('client_id', $v) && !empty($v['client_id'])) {
        $payload['client_id'] = $v['client_id'];
    }
    
    if (array_key_exists('status', $v)) {
        $payload['status'] = $v['status'];
    }
    
    // ✅ ADICIONAR PROCESSAMENTO DO DESCONTO
    if (array_key_exists('discount_total_override', $v)) {
        $newDiscount = (float)($v['discount_total_override'] ?? 0);
        $payload['discount_total'] = $newDiscount;
        
        // ✅ RECALCULAR total_amount considerando novo desconto
        $sumLines = (float) $order->items()->sum('line_total');
        $sumItemDisc = (float) $order->items()->sum('discount_value');
        $netItems = max(0.0, $sumLines - $sumItemDisc);
        $payload['total_amount'] = max(0.0, $netItems - $newDiscount + (float)($order->addition_total ?? 0));
    }
    
    $order->update($payload);
    return back()->with('success','Pedido atualizado com sucesso.');
}
```

### ✅ CORREÇÃO ADICIONAL: Preservar Desconto ao Adicionar/Remover Itens

**Arquivo**: `app/Http/Controllers/OrderController.php` método `recalculateTotals()` linha 810

```php
// CÓDIGO ATUAL (ERRADO):
private function recalculateTotals(Order $order): void
{
    $sum = OrderItem::where('order_id', $order->id)->sum('line_total');
    $order->total_amount = (float) $sum;
    // Mantemos discount_total/addition_total zerados neste fluxo  // ❌ COMENTÁRIO ERRADO
    $order->save();
}

// ✅ SUBSTITUIR POR:
private function recalculateTotals(Order $order): void
{
    // Soma line_total e discount_value dos itens
    $sumLines = (float) OrderItem::where('order_id', $order->id)->sum('line_total');
    $sumItemDisc = (float) OrderItem::where('order_id', $order->id)->sum('discount_value');
    
    // Calcula líquido dos itens (já considerando descontos por item)
    $netItems = max(0.0, $sumLines - $sumItemDisc);
    
    // Aplica desconto total do pedido (NÃO ZERAR!)
    $headerDiscount = (float)($order->discount_total ?? 0);
    $headerAddition = (float)($order->addition_total ?? 0);
    
    // Total final = itens líquidos - desconto total + acréscimos
    $order->total_amount = max(0.0, $netItems - $headerDiscount + $headerAddition);
    
    // NÃO alterar discount_total nem addition_total aqui!
    $order->save();
}
```

### Teste de Validação
Depois da correção:
1. Criar pedido com 2 itens totalizando R$ 1.000,00
2. Aplicar desconto geral de R$ 100,00 → salvar
3. Verificar banco: `discount_total` deve ser 100.00
4. Verificar banco: `total_amount` deve ser 900.00
5. Adicionar mais 1 item de R$ 500,00
6. Verificar banco: `discount_total` ainda deve ser 100.00
7. Verificar banco: `total_amount` deve ser 1.400,00 (1500 - 100)

---

## PROBLEMA #3: XML da NF-e com Dados Incorretos

### Localização
- `app/Services/NFeService.php` método `buildOrderPayload()` linhas 813-986
- `app/Http/Controllers/OrderController.php` método `issueNfe()` linhas 1297-1527

### Descrição dos Problemas

#### Sub-problema 3A: Produtos sem Dados Fiscais
O XML é montado com produtos que podem ter:
- NCM vazio ou incompleto (precisa 8 dígitos)
- CST/CSOSN vazio
- Alíquotas zeradas de ICMS/PIS/COFINS
- Origem não informada

```php
// NFeService.php linha 819-842
$produtos[] = [
    'ncm' => (string) ($product->ncm ?? ''),      // ⚠️ PODE SER VAZIO
    'cest' => (string) ($product->cest ?? ''),    // ⚠️ PODE SER VAZIO
    'origem' => (int) ($product->origin ?? 0),    // ⚠️ PODE SER 0 (inválido)
    'quantidade' => $qtd,
    'valor_unitario' => $unit,
    'valor_total' => $line,
];
```

**Resultado**: SEFAZ **rejeita** a nota com códigos de erro:
- 758: NCM de informação obrigatória
- 759: CEST inválido
- Diversos erros de CST/alíquotas

#### Sub-problema 3B: Totais Errados Devido ao Bug do Desconto
Como o `total_amount` pode estar calculado errado (Problema #1 e #2), o XML pode ter:
- `vProd` correto
- `vDesc` incorreto (pode estar zerado quando deveria ter valor)
- `vNF` incorreto (total da nota errado)

```php
// NFeService.php linha 976-982
'totais' => [
    'vProd' => number_format((float)$totalVProd, 2, '.', ''),
    'vDesc' => number_format((float)$totalDesc, 2, '.', ''),  // ⚠️ Pode estar zerado
    'vFrete' => number_format((float)$totalFrete, 2, '.', ''),
    'vSeg' => number_format((float)$totalSeg, 2, '.', ''),
    'vOutro' => number_format((float)$totalOutro, 2, '.', ''),
    'vNF' => number_format((float)$vNF, 2, '.', ''),  // ⚠️ Pode estar errado
],
```

#### Sub-problema 3C: Cliente sem Dados Completos
Cliente pode não ter:
- Endereço completo (logradouro, número, bairro)
- CEP válido
- Código IBGE do município
- CPF/CNPJ inválido ou faltando

### ✅ CORREÇÃO NECESSÁRIA - Parte 1: Validação Pré-Emissão

**Arquivo**: `app/Http/Controllers/OrderController.php` - **ADICIONAR** novo método antes de `issueNfe()`

```php
/**
 * Valida se o pedido tem todos os dados necessários para emitir NF-e
 * 
 * @param Order $order
 * @return array Array de erros (vazio se tudo OK)
 */
private function validateOrderForNfe(Order $order): array
{
    $errors = [];
    
    // ====== VALIDAÇÃO DE PRODUTOS ======
    if ($order->items->count() === 0) {
        $errors[] = "Pedido sem itens. Adicione produtos antes de emitir a nota.";
        return $errors; // Retorna imediatamente se não há itens
    }
    
    foreach ($order->items as $index => $item) {
        $itemNum = $index + 1;
        $prod = $item->product;
        
        if (!$prod) {
            $errors[] = "Item #{$itemNum} ('{$item->name}') não está vinculado a um produto. Configure o vínculo.";
            continue;
        }
        
        // NCM obrigatório e com 8 dígitos
        $ncm = preg_replace('/\D/', '', (string)($prod->ncm ?? ''));
        if (strlen($ncm) !== 8) {
            $errors[] = "Produto '{$prod->name}' (Item #{$itemNum}): NCM inválido ou faltando. Deve ter exatamente 8 dígitos.";
        }
        
        // CST/CSOSN obrigatório
        $cst = (string)($prod->cst ?? $prod->cst_icms ?? '');
        if (empty($cst)) {
            $errors[] = "Produto '{$prod->name}' (Item #{$itemNum}): CST/CSOSN não informado. Configure a tributação do produto.";
        }
        
        // CFOP obrigatório
        $cfop = (string)($prod->cfop ?? '');
        if (empty($cfop) || strlen($cfop) < 4) {
            $errors[] = "Produto '{$prod->name}' (Item #{$itemNum}): CFOP inválido ou não informado.";
        }
        
        // Alíquotas (avisar se todas estão zeradas - pode ser correto para alguns CSTs)
        $aliqIcms = (float)($prod->aliquota_icms ?? 0);
        $aliqPis = (float)($prod->aliquota_pis ?? 0);
        $aliqCofins = (float)($prod->aliquota_cofins ?? 0);
        
        if ($aliqIcms <= 0 && $aliqPis <= 0 && $aliqCofins <= 0) {
            // Apenas aviso, não bloqueia (pode ser produto isento)
            $errors[] = "⚠️ Produto '{$prod->name}' (Item #{$itemNum}): Todas as alíquotas estão zeradas. Verifique se está correto.";
        }
        
        // Origem obrigatória (0 a 8)
        $origem = (int)($prod->origin ?? -1);
        if ($origem < 0 || $origem > 8) {
            $errors[] = "Produto '{$prod->name}' (Item #{$itemNum}): Origem da mercadoria não informada (0=Nacional, 1=Estrangeira, etc).";
        }
        
        // Unidade obrigatória
        $unit = (string)($prod->unit ?? '');
        if (empty($unit)) {
            $errors[] = "Produto '{$prod->name}' (Item #{$itemNum}): Unidade de medida não informada (UN, KG, etc).";
        }
    }
    
    // ====== VALIDAÇÃO DE CLIENTE ======
    $cliente = $order->client;
    if (!$cliente) {
        $errors[] = "Pedido sem cliente. Selecione um cliente antes de emitir a nota.";
        return $errors; // Retorna imediatamente
    }
    
    // CPF/CNPJ obrigatório (exceto consumidor final)
    $isConsumidorFinal = (string)($cliente->name ?? '') === 'Consumidor Final' 
                      || (string)($cliente->consumidor_final ?? '') === 'S';
    
    if (!$isConsumidorFinal) {
        $doc = preg_replace('/\D/', '', (string)($cliente->cpf_cnpj ?? ''));
        if (empty($doc)) {
            $errors[] = "Cliente '{$cliente->name}': CPF/CNPJ não informado.";
        } else {
            // Validação básica de tamanho
            if (strlen($doc) !== 11 && strlen($doc) !== 14) {
                $errors[] = "Cliente '{$cliente->name}': CPF/CNPJ inválido (deve ter 11 ou 14 dígitos).";
            }
        }
    }
    
    // Endereço obrigatório
    if (empty($cliente->address) || empty($cliente->number)) {
        $errors[] = "Cliente '{$cliente->name}': Endereço incompleto (falta logradouro ou número).";
    }
    
    if (empty($cliente->neighborhood)) {
        $errors[] = "Cliente '{$cliente->name}': Bairro não informado.";
    }
    
    if (empty($cliente->city) || empty($cliente->state)) {
        $errors[] = "Cliente '{$cliente->name}': Cidade ou UF não informada.";
    }
    
    // CEP obrigatório
    $cep = preg_replace('/\D/', '', (string)($cliente->zip_code ?? ''));
    if (strlen($cep) !== 8) {
        $errors[] = "Cliente '{$cliente->name}': CEP inválido ou não informado (deve ter 8 dígitos).";
    }
    
    // Código IBGE obrigatório
    $ibge = (int)($cliente->codigo_ibge ?? $cliente->codigo_municipio ?? 0);
    if ($ibge === 0) {
        $errors[] = "Cliente '{$cliente->name}': Código IBGE do município não informado.";
    }
    
    // ====== VALIDAÇÃO DE TOTAIS ======
    // Verificar se total_amount está consistente
    $sumLines = $order->items->sum('line_total');
    $sumItemDisc = $order->items->sum('discount_value');
    $headerDisc = (float)($order->discount_total ?? 0);
    $calculatedNet = max(0.0, $sumLines - $sumItemDisc - $headerDisc);
    $savedNet = (float)($order->total_amount ?? 0);
    
    // Tolerância de 0.02 centavos para arredondamentos
    if (abs($calculatedNet - $savedNet) > 0.02) {
        $errors[] = "INCONSISTÊNCIA: Total calculado (R$ " . number_format($calculatedNet, 2, ',', '.') 
                  . ") difere do total salvo (R$ " . number_format($savedNet, 2, ',', '.') 
                  . "). Reabra e salve o pedido novamente.";
    }
    
    // ====== VALIDAÇÃO DE PAGAMENTO ======
    $receivables = \App\Models\Receivable::where('tenant_id', $order->tenant_id)
        ->where('order_id', $order->id)
        ->count();
    
    if ($receivables === 0) {
        $errors[] = "Nenhuma forma de pagamento definida. Finalize o pedido antes de emitir a NF-e.";
    }
    
    return $errors;
}
```

### ✅ CORREÇÃO NECESSÁRIA - Parte 2: Chamar Validação em issueNfe()

**Arquivo**: `app/Http/Controllers/OrderController.php` método `issueNfe()` linha 1297

```php
public function issueNfe(Order $order, Request $request)
{
    abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
    abort_unless(auth()->user()->hasPermission('nfe.emit'), 403);
    abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
    \Log::info('Orders.issueNfe called', ['order_id' => $order->id, 'status' => $order->status]);
    
    if ($order->status !== 'fulfilled') {
        return back()->with('error', 'Para emitir NF-e, o pedido precisa estar Finalizado.');
    }
    
    if (!$this->hasPaymentDefinition($order)) {
        return back()->with('error', 'Defina a forma/condição de pagamento antes de emitir a NF-e.')
                    ->with('action', route('orders.payment', $order));
    }
    
    // ✅ ADICIONAR VALIDAÇÃO PRÉ-EMISSÃO (ANTES DE QUALQUER PROCESSAMENTO)
    $validationErrors = $this->validateOrderForNfe($order);
    if (!empty($validationErrors)) {
        $errorList = '<ul class="list-disc pl-5">';
        foreach ($validationErrors as $err) {
            $errorList .= '<li>' . e($err) . '</li>';
        }
        $errorList .= '</ul>';
        
        return back()->with('error', 'Corrija os seguintes problemas antes de emitir a NF-e:')
                    ->with('validation_errors', $errorList);
    }
    
    // ... resto do código continua igual ...
}
```

### ✅ CORREÇÃO NECESSÁRIA - Parte 3: Corrigir Cálculo de Totais no Payload

**Arquivo**: `app/Services/NFeService.php` método `buildOrderPayload()` linha 844-851

```php
// LOCALIZAR ESTE TRECHO (por volta da linha 844):
$totalVProd = 0.0; $totalDesc = 0.0; $totalFrete = 0.0; $totalSeg = 0.0; $totalOutro = 0.0;
foreach ($order->items as $item) {
    // ...
    $totalVProd += $line;
    // ...
}

// ✅ CORRIGIR O CÁLCULO DOS TOTAIS:
try {
    // Desconto total = soma dos descontos por item + desconto do cabeçalho
    $totalDescItens = (float) $order->items->sum('discount_value');
    $totalDescHeader = (float) ($order->discount_total ?? 0);
    $totalDesc = $totalDescItens + $totalDescHeader;
    
    // Frete, seguro e outras despesas
    $totalFrete = (float) ($order->freight_cost ?? 0);
    $totalSeg = (float) ($order->valor_seguro ?? 0);
    $totalOutro = (float) ($order->outras_despesas ?? 0);
    
    // Acréscimos
    $totalAcrescimo = (float) ($order->addition_total ?? 0);
} catch (\Throwable $e) {
    \Log::error('Erro ao calcular totais do pedido para NF-e', [
        'order_id' => $order->id,
        'error' => $e->getMessage()
    ]);
}

// vNF = vProd - vDesc + vFrete + vSeg + vOutro + vAcresc
$vNF = max(0.0, ($totalVProd - $totalDesc) + $totalFrete + $totalSeg + $totalOutro + $totalAcrescimo);
```

### ✅ CORREÇÃO NECESSÁRIA - Parte 4: Garantir Campos Obrigatórios no Produto

**Arquivo**: `app/Services/NFeService.php` método `buildOrderPayload()` linha 819-842

```php
// SUBSTITUIR O LOOP DE PRODUTOS POR:
$produtos = [];
$totalVProd = 0.0;

foreach ($order->items as $item) {
    $product = $item->product;
    if (!$product) {
        // Produto já foi validado em validateOrderForNfe, mas por segurança:
        throw new \Exception("Item '{$item->name}' sem produto vinculado");
    }
    
    $qtd = (float) $item->quantity;
    $unit = (float) $item->unit_price;
    $line = round($qtd * $unit, 2);
    $totalVProd += $line;
    
    // SKU/código do produto (obrigatório para ACBr)
    $skuVal = trim((string) ($product->sku ?? ''));
    if ($skuVal === '') {
        $skuVal = 'PROD-' . str_pad((string)$item->product_id, 6, '0', STR_PAD_LEFT);
    }
    
    // NCM formatado (remover pontos/traços, apenas números)
    $ncm = preg_replace('/\D/', '', (string)($product->ncm ?? ''));
    if (strlen($ncm) !== 8) {
        $ncm = '00000000'; // Fallback (já deve ter sido bloqueado na validação)
    }
    
    // CEST (opcional, mas se tiver, formatar)
    $cest = preg_replace('/\D/', '', (string)($product->cest ?? ''));
    
    // Origem (0 a 8)
    $origem = (int)($product->origin ?? 0);
    if ($origem < 0 || $origem > 8) {
        $origem = 0; // Nacional como fallback
    }
    
    $produtos[] = [
        'id' => $item->product_id,
        'nome' => (string) ($product->name ?? $item->name ?? 'Produto'),
        'codigo' => $skuVal,
        'cProd' => $skuVal,
        'ncm' => $ncm,  // ✅ Garantido 8 dígitos
        'cest' => $cest,
        'origem' => $origem,  // ✅ Garantido 0-8
        'quantidade' => $qtd,
        'valor_unitario' => $unit,
        'unidade' => (string) ($product->unit ?? $item->unit ?? 'UN'),
        'valor_total' => $line,
        // ✅ Adicionar dados tributários (serão usados pelo emissor)
        'cfop' => (string) ($product->cfop ?? '5102'),
        'cst' => (string) ($product->cst ?? $product->cst_icms ?? ''),
        'aliquota_icms' => (float) ($product->aliquota_icms ?? 0),
        'aliquota_pis' => (float) ($product->aliquota_pis ?? 0),
        'aliquota_cofins' => (float) ($product->aliquota_cofins ?? 0),
    ];
}
```

### Teste de Validação Completo

Depois de todas as correções:

1. **Teste de Produto Incompleto**:
   - Criar produto sem NCM
   - Adicionar ao pedido
   - Tentar emitir NF-e
   - Deve REJEITAR com mensagem clara

2. **Teste de Cliente Incompleto**:
   - Criar cliente sem CEP
   - Criar pedido para este cliente
   - Tentar emitir NF-e
   - Deve REJEITAR com mensagem clara

3. **Teste de Totais**:
   - Criar pedido: 3 itens = R$ 1.000,00
   - Desconto item 1: R$ 50,00
   - Desconto geral: R$ 100,00
   - Frete: R$ 50,00
   - Emitir NF-e
   - Verificar XML:
     - `vProd` = 1000.00
     - `vDesc` = 150.00 (50 + 100)
     - `vFrete` = 50.00
     - `vNF` = 900.00 (1000 - 150 + 50)

4. **Teste de Desconto Persistente**:
   - Criar pedido com desconto geral R$ 200,00
   - Salvar
   - Fechar e reabrir navegador
   - Editar pedido
   - Desconto deve aparecer como R$ 200,00
   - Adicionar novo item
   - Desconto deve continuar R$ 200,00

---

## RESUMO DAS ALTERAÇÕES

### Arquivos a Modificar

1. **resources/views/orders/index.blade.php** (linhas 162-176)
   - Remover subtração duplicada de `discount_total`

2. **app/Http/Controllers/OrderController.php**
   - Método `update()` (linha 353): Adicionar processamento de `discount_total_override`
   - Método `recalculateTotals()` (linha 810): Preservar desconto ao recalcular
   - **ADICIONAR** método `validateOrderForNfe()` (novo)
   - Método `issueNfe()` (linha 1297): Chamar validação antes de emitir

3. **app/Services/NFeService.php**
   - Método `buildOrderPayload()` (linha 813-986):
     - Corrigir cálculo de totais
     - Garantir campos obrigatórios nos produtos

### Impacto Esperado

✅ **Depois das correções**:
- Valor líquido correto em listagem e relatórios
- Desconto geral salva e persiste ao adicionar/remover itens
- Validação impede emissão de NF-e com dados incompletos
- XML gerado com totais corretos
- Menos rejeições da SEFAZ
- Conformidade fiscal garantida

---

## INSTRUÇÕES PARA O GPT-5

```
Por favor, implemente TODAS as correções descritas acima nos arquivos indicados.

IMPORTANTE:
1. Preserve a estrutura e formatação existente do código
2. Adicione comentários marcados com ✅ nas linhas modificadas
3. Teste cada correção isoladamente antes de passar para a próxima
4. Se algum método referenciado não existir, crie-o conforme especificado
5. Mantenha compatibilidade com as permissões existentes (hasPermission)
6. Não remova funcionalidades existentes, apenas corrija os bugs

ORDEM DE IMPLEMENTAÇÃO SUGERIDA:
1. Correção #1 (mais simples, menos impacto)
2. Correção #2 (crítica para UX)
3. Correção #3 (mais complexa, maior impacto)

Após implementar, por favor confirme:
- Quais arquivos foram modificados
- Quantas linhas foram alteradas em cada
- Se alguma funcionalidade adicional foi necessária
```

---

**Gerado em**: 11/10/2025  
**Versão**: 1.0  
**Sistema**: QFiscal - Módulo de Pedidos e NF-e

