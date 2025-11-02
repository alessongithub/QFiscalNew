# Análise: Fluxo de Devoluções, Descontos e NFe

## Situação Atual

### 1. Erro no Print (CORRIGIDO)
- **Problema**: `icmsSuggestions` estava sendo tratado como array de strings, mas pode vir como array de arrays
- **Correção**: Ajustado para tratar ambos os casos (string ou array com `product_name` e `suggestion`)

### 2. Fluxo de Devoluções Parciais

**Situação atual:**
- Quando há devolução parcial, o pedido é marcado como `partial_returned`
- Itens mantêm quantidade original, mas exibem quantidade devolvida visualmente
- Ao reabrir o pedido, apenas **avisa** o usuário sobre devoluções, mas não ajusta automaticamente

**Problemas identificados:**
1. **Descontos não ajustados**: Se um item tinha desconto proporcional à quantidade total (ex: R$ 3,00 para 10 unidades), o desconto não é ajustado quando há devolução parcial
2. **Quantidades originais mantidas**: O pedido ainda mostra quantidade original, o que pode confundir na emissão de nova NFe
3. **Não há opção automática**: Usuário precisa ajustar manualmente cada item

### 3. NFe e Reabertura de Pedidos

**Situação atual:**
```php
// Linha 1733-1734: Bloqueia reabertura se há NFe transmitida
if ($hasSuccessfulNfe || !empty($order->nfe_issued_at)) {
    return back()->with('error', 'Pedido com NF-e transmitida não pode ser reaberto.');
}
```

**Cenários:**
1. **Pedido com NFe transmitida + devolução parcial**:
   - ❌ Não pode ser reaberto
   - ✅ Deve emitir NFe de **devolução** (operação diferente)
   - ⚠️ A NFe original continua válida

2. **Pedido sem NFe + devolução parcial**:
   - ✅ Pode ser reaberto
   - ✅ Pode ajustar itens e descontos
   - ✅ Pode emitir NFe com valores corretos

3. **Pedido com NFe transmitida + devolução total**:
   - ❌ Não pode ser reaberto
   - ✅ Deve **cancelar** a NFe original
   - ✅ Ou emitir NFe de devolução

## Recomendações

### Opção 1: Ajuste Automático (Recomendado para MVP)
Quando pedido é reaberto após devolução parcial:
1. **Detectar itens com devoluções parciais**
2. **Remover item original**
3. **Criar novo item com quantidade restante**
4. **Aplicar desconto proporcional** ou **zerar desconto** (escolher política)
5. **Recalcular totais automaticamente**

**Vantagens:**
- ✅ Evita erros manuais
- ✅ Pedido fica consistente
- ✅ Facilita emissão de nova NFe

**Desvantagens:**
- ❌ Remove flexibilidade (usuário não pode manter desconto proporcional)
- ❌ Pode não atender casos específicos

### Opção 2: Ajuste Semi-Automático (Atual)
Quando pedido é reaberto após devolução parcial:
1. **Avisar usuário** sobre itens com devoluções
2. **Sugerir ação**: "Remover item e criar novo com quantidade restante?"
3. **Botão opcional**: "Ajustar automaticamente" (remove + cria novo sem desconto)
4. **Usuário pode fazer manualmente** se preferir

**Vantagens:**
- ✅ Mantém flexibilidade
- ✅ Usuário decide estratégia de desconto
- ✅ Evita ações não desejadas

**Desvantagens:**
- ❌ Usuário pode esquecer de ajustar
- ❌ Pode emitir NFe com valores incorretos

### Opção 3: Híbrida (Melhor para Produção)
1. **Reabertura automática**: Ajusta itens automaticamente ao reabrir
2. **Modal de confirmação**: Mostra o que será feito antes de aplicar
3. **Opção de reverter**: Se usuário não concordar, pode cancelar a reabertura
4. **Log de mudanças**: Registra na auditoria o que foi ajustado

## Fluxo de NFe com Devolução Parcial

### Cenário: Pedido com NFe emitida + Devolução Parcial

**Não permitir reabertura** (atual):
- ✅ Correto: NFe já transmitida não pode ser alterada
- ✅ Solução: Emitir **NFe de Devolução** (operação tipo 1/1A)
- ✅ A NFe original continua válida
- ✅ NFe de devolução referencia a NFe original

**Implementação sugerida:**
```php
// Em ReturnController, após processar devolução:
if ($order->has_successful_nfe && $isPartialReturn) {
    // Sugerir emitir NFe de devolução
    return redirect()->route('orders.edit', $order)
        ->with('info', 'Para devoluções parciais de pedido com NFe, emita uma NFe de devolução.')
        ->with('suggest_nfe_return', true);
}
```

### Cenário: Pedido sem NFe + Devolução Parcial + Reabertura

**Fluxo recomendado:**
1. ✅ Permitir reabertura (sem NFe transmitida)
2. ✅ Ajustar itens automaticamente (ou semi-automático)
3. ✅ Permitir emitir NFe com valores corretos

## Redundâncias Identificadas

### 1. Cálculo de Quantidade Devolvida
- **Ocorrências**: `orders/show.blade.php`, `orders/edit.blade.php`, `OrderController@reopen`
- **Problema**: Mesma lógica repetida em 3 lugares
- **Solução**: Criar método no modelo `Order` ou `OrderItem`:
  ```php
  // OrderItem.php
  public function getReturnedQuantityAttribute(): float {
      return (float) ReturnItem::whereIn('return_id', 
          ReturnModel::where('order_id', $this->order_id)->pluck('id')
      )->where('order_item_id', $this->id)->sum('quantity');
  }
  ```

### 2. Verificação de Status NFe
- **Ocorrências**: `OrderController@reopen`, `OrderController@issueNfe`, `orders/edit.blade.php`
- **Problema**: Lógica repetida
- **Solução**: Criar método no modelo `Order`:
  ```php
  public function canBeReopened(): bool {
      $latestNfe = $this->latestNfeNoteCompat;
      $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
      return !in_array($nfeStatus, ['emitted','transmitida']) && empty($this->nfe_issued_at);
  }
  ```

## Implementações Necessárias

### Prioridade Alta
1. ✅ **Corrigir erro no print** (FEITO)
2. ⚠️ **Decidir política de ajuste automático vs manual**
3. ⚠️ **Melhorar aviso de devoluções parciais** (adicionar botão opcional)

### Prioridade Média
4. **Criar métodos helpers** para evitar redundâncias
5. **Implementar sugestão de NFe de devolução** quando houver NFe transmitida

### Prioridade Baixa
6. **Modal de confirmação** para ajuste automático
7. **Log detalhado** de mudanças automáticas na reabertura

## Decisão Necessária

**Qual política adotar para ajuste de descontos na reabertura?**

- [ ] **A) Automático**: Remove item e cria novo sem desconto (simples, rápido)
- [ ] **B) Semi-automático atual**: Avisa e deixa usuário ajustar (flexível, mas pode esquecer)
- [ ] **C) Híbrida**: Ajusta automaticamente, mas permite reverter ou customizar

**Recomendação**: Opção C (Híbrida) para melhor UX e segurança.

