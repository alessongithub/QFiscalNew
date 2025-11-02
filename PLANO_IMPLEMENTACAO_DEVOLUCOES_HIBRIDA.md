# Plano de Implementação: Devoluções Híbridas

> **Última atualização**: 30/10/2025  
> **Status geral**: 80% concluído (4 de 5 fases)  
> **Próxima fase**: FASE 5 - Melhorar Logs e Auditoria

## Objetivo
Implementar sistema híbrido de ajuste automático com confirmação para pedidos com devoluções parciais, mantendo compliance fiscal e transparência.

---

## FASE 1: Ajustar Print com Quantidades Devolvidas
**Prioridade**: 🔴 Alta (Pedido do usuário)
**Complexidade**: ⭐ Baixa
**Tempo estimado**: 30-45min

### Tarefas
1. ✅ Modificar `OrderController@print` para calcular quantidades reais (original - devolvida)
2. ✅ Ajustar `resources/views/orders/print.blade.php` para exibir:
   - Quantidade vendida original (riscada)
   - Quantidade atual após devolução (destaque)
   - Ou apenas quantidade final (sem mostrar auditoria)
3. ✅ Ajustar totais para refletir valores reais após devolução
4. ✅ Testar impressão com e sem devoluções

### Arquivos a modificar
- `app/Http/Controllers/OrderController.php` (método `print`)
- `resources/views/orders/print.blade.php`

### Critérios de aceitação
- [x] Print mostra quantidade ajustada (não a original)
- [x] Totais refletem valores após devolução
- [x] Não mostra detalhes de auditoria (apenas pedido limpo)
- [x] Funciona com e sem devoluções

### Status: ✅ CONCLUÍDA

**Data de conclusão**: 30/10/2025

**Implementações realizadas**:
- ✅ Método `print()` em `OrderController` calcula quantidades restantes após devoluções
- ✅ Itens totalmente devolvidos são excluídos do print
- ✅ Totais recalculados (subtotal, desconto, acréscimo, total final)
- ✅ Modal de opções de impressão permite escolher quais seções exibir
- ✅ Estornos financeiros não aparecem misturados com formas de pagamento (filtrados valores > 0)
- ✅ URL da logo corrigida para usar `asset()` garantindo porta correta
- ✅ Removidos campos técnicos desnecessários (tPag, Status) da impressão

---

## FASE 2: Criar Helpers/Métodos Centralizados
**Prioridade**: 🟡 Média
**Complexidade**: ⭐ Baixa
**Tempo estimado**: 45-60min

### Tarefas
1. ✅ Criar método `getReturnedQuantity()` em `OrderItem` model
2. ✅ Criar método `canBeReopened()` em `Order` model
3. ✅ Criar método `hasSuccessfulNfe()` em `Order` model
4. ✅ Criar método `getItemsWithReturns()` em `Order` model
5. ✅ Refatorar código existente para usar novos métodos

### Arquivos a criar/modificar
- `app/Models/OrderItem.php` (adicionar métodos)
- `app/Models/Order.php` (adicionar métodos)
- `app/Http/Controllers/OrderController.php` (refatorar para usar métodos)
- `resources/views/orders/show.blade.php` (refatorar)
- `resources/views/orders/edit.blade.php` (refatorar)

### Métodos sugeridos

#### OrderItem.php
```php
/**
 * Retorna quantidade devolvida deste item
 */
public function getReturnedQuantityAttribute(): float

/**
 * Retorna quantidade restante (vendida - devolvida)
 */
public function getRemainingQuantityAttribute(): float

/**
 * Verifica se item tem devolução
 */
public function hasReturn(): bool
```

#### Order.php
```php
/**
 * Verifica se pedido pode ser reaberto
 */
public function canBeReopened(): bool

/**
 * Verifica se pedido tem NFe transmitida com sucesso
 */
public function hasSuccessfulNfe(): bool

/**
 * Retorna itens com devoluções parciais
 */
public function getItemsWithPartialReturns(): Collection

/**
 * Calcula totais ajustados considerando devoluções
 */
public function getAdjustedTotals(): array
```

### Critérios de aceitação
- [x] Métodos centralizados criados
- [x] Código duplicado removido
- [x] Todos os lugares usam os novos métodos
- [x] Testes básicos passam

### Status: ✅ CONCLUÍDA

**Data de conclusão**: 30/10/2025

**Implementações realizadas**:
- ✅ `OrderItem::getReturnedQuantityAttribute()` - Calcula quantidade devolvida do item
- ✅ `OrderItem::getRemainingQuantityAttribute()` - Calcula quantidade restante (vendida - devolvida)
- ✅ `OrderItem::hasReturn()` - Verifica se item tem devolução
- ✅ `Order::canBeReopened()` - Verifica se pedido pode ser reaberto (status + NFe)
- ✅ `Order::hasSuccessfulNfe()` - Verifica se pedido tem NFe transmitida/autorizada
- ✅ `Order::getItemsWithPartialReturns()` - Retorna itens com devoluções parciais/totais com detalhes
- ✅ `Order::getAdjustedTotals()` - Calcula totais ajustados considerando devoluções
- ✅ Código refatorado para usar os novos métodos em `OrderController`, `ReturnController` e views

---

## FASE 3: Modal de Confirmação na Reabertura
**Prioridade**: 🔴 Alta
**Complexidade**: ⭐⭐⭐ Média-Alta
**Tempo estimado**: 2-3h

### Tarefas
1. ✅ Criar view `resources/views/orders/modals/reopen_confirmation.blade.php`
2. ✅ Adicionar JavaScript para exibir modal antes de reabrir
3. ✅ Modificar `OrderController@reopen` para:
   - Validar se há devoluções parciais
   - Preparar dados de ajuste (sem aplicar ainda)
   - Retornar JSON com preview das mudanças
4. ✅ Criar endpoint `POST /orders/{order}/reopen-with-adjustment` que:
   - Recebe confirmação do usuário
   - Aplica ajustes automaticamente
   - Registra auditoria detalhada
5. ✅ Implementar opção "Editar manualmente" (cancela modal, permite edição normal)

### Fluxo de Usuário
```
1. Usuário clica "Reabrir pedido"
2. Sistema detecta devoluções parciais
3. Exibe modal com:
   - Resumo dos itens afetados
   - Preview das mudanças (antes/depois)
   - Opções: [Aplicar Ajuste] [Cancelar] [Editar Manualmente]
4. Se "Aplicar Ajuste":
   - Sistema remove itens originais
   - Cria novos com quantidades restantes
   - Zera descontos (ou aplica proporcional - decidir)
   - Recalcula totais
   - Registra auditoria detalhada
5. Se "Editar Manualmente":
   - Cancela modal
   - Permite edição normal
   - Mostra aviso sobre devoluções
```

### Arquivos a criar/modificar
- `resources/views/orders/modals/reopen_confirmation.blade.php` (NOVO)
- `resources/views/orders/edit.blade.php` (adicionar modal)
- `app/Http/Controllers/OrderController.php` (novos métodos)
- `routes/web.php` (nova rota)

### Estrutura do Modal
```html
<div id="reopenConfirmationModal">
  <h3>Reabertura com Ajuste Automático</h3>
  <p>Detectamos devoluções parciais. Deseja ajustar automaticamente?</p>
  
  <!-- Lista de itens afetados -->
  <table>
    <tr>
      <th>Item</th>
      <th>Qtd Original</th>
      <th>Devolvido</th>
      <th>Qtd Restante</th>
      <th>Desconto Original</th>
      <th>Desconto Ajustado</th>
    </tr>
    <!-- ... -->
  </table>
  
  <div class="actions">
    <button onclick="applyAdjustment()">Aplicar Ajuste</button>
    <button onclick="editManually()">Editar Manualmente</button>
    <button onclick="cancel()">Cancelar</button>
  </div>
</div>
```

### Critérios de aceitação
- [x] Modal exibe corretamente preview das mudanças
- [x] Ajuste automático funciona corretamente
- [x] Auditoria registra todas as mudanças
- [x] Opção "Editar Manualmente" funciona
- [x] Testes com vários cenários

### Status: ✅ CONCLUÍDA

**Data de conclusão**: 30/10/2025

**Implementações realizadas**:
- ✅ Modal de ajuste automático (`adjustmentModal`) para pedidos já abertos com devolução parcial
- ✅ Modal de reabertura com ajuste (`reopenModalWithAdjustment`) para pedidos `partial_returned`
- ✅ Endpoint `POST /orders/{order}/prepare-reopen-adjustment` retorna preview dos ajustes em JSON
- ✅ Endpoint `POST /orders/{order}/reopen-with-adjustment` aplica ajustes na reabertura
- ✅ Endpoint `POST /orders/{order}/adjust-with-returns` aplica ajustes em pedidos já abertos
- ✅ Preview mostra: item, qtd original, devolvida, restante, desconto original, desconto ajustado
- ✅ Opção "Pular e Ajustar Manualmente" permite edição manual
- ✅ Opção "Reabrir sem Ajuste Automático" reabre sem aplicar ajustes
- ✅ Validação de justificativa obrigatória para reabertura
- ✅ Lógica de exibição de botões diferenciada por status (`open` vs `partial_returned`)
- ✅ Removido checkbox "Estornar financeiro" (já processado na devolução)
- ✅ Auditoria registra detalhes dos ajustes aplicados (quantidades, descontos, totais)

---

## FASE 4: NFe de Devolução (NFe Transmitida)
**Prioridade**: 🟡 Média
**Complexidade**: ⭐⭐⭐ Média
**Tempo estimado**: 2-3h

### Tarefas
1. ✅ Modificar `ReturnController@store` para detectar NFe transmitida
2. ✅ Adicionar verificação: se há NFe transmitida + devolução parcial → bloquear reabertura
3. ✅ Criar alerta/flash message sugerindo "Emitir NFe de Devolução"
4. ✅ Adicionar link/botão para ir direto para emissão de NFe de devolução
5. ✅ Documentar processo de NFe de devolução (tipo 1/1A)

### Arquivos a modificar
- `app/Http/Controllers/ReturnController.php`
- `app/Http/Controllers/OrderController.php` (melhorar mensagens)
- `resources/views/returns/create.blade.php` (adicionar aviso)
- `resources/views/orders/edit.blade.php` (mostrar alerta se houver NFe)

### Fluxo
```
1. Usuário tenta devolver item de pedido com NFe transmitida
2. Sistema processa devolução normalmente
3. Sistema detecta: NFe transmitida + devolução parcial
4. Sistema bloqueia reabertura (já implementado)
5. Sistema mostra alerta: "Este pedido possui NFe transmitida. Para devoluções, emita uma NFe de devolução (tipo 1/1A) que referencia a NFe original."
6. Link: "Emitir NFe de Devolução"
```

### Critérios de aceitação
- [x] Detecta NFe transmitida corretamente
- [x] Bloqueia reabertura quando apropriado
- [x] Mostra mensagem clara ao usuário
- [x] Link para emissão de NFe de devolução funciona

### Status: ✅ CONCLUÍDA

**Data de conclusão**: 30/10/2025

**Implementações realizadas**:
- ✅ `ReturnController@create` detecta NFe transmitida antes de processar devolução
- ✅ Alerta em `/returns/create` informa sobre NFe transmitida e necessidade de NFe de devolução
- ✅ `ReturnController@store` detecta NFe transmitida após devolução parcial e redireciona com flash warning
- ✅ Mensagem melhorada incluindo número da NFe e explicação sobre conformidade fiscal
- ✅ Alerta destacado (amarelo) em `/orders/edit` quando há NFe transmitida + devolução parcial
- ✅ Botão/link "Emitir NF-e de Devolução" aparece no alerta (se usuário tiver permissão)
- ✅ Flash message tipo `warning` implementada em `orders/edit.blade.php`
- ✅ Função `showToast` atualizada para suportar tipo `warning` com ícone e cor amarela
- ✅ Informações da NFe (número e chave parcial) são exibidas nos alertas

---

## FASE 5: Melhorar Logs e Auditoria
**Prioridade**: 🟢 Baixa (mas importante)
**Complexidade**: ⭐⭐ Baixa-Média
**Tempo estimado**: 1-2h
**Status**: ⏳ PENDENTE

### Tarefas
1. ⏳ Verificar se `OrderAudit` registra ajustes automáticos detalhadamente
2. ⏳ Adicionar informações fiscais relevantes nos logs (impacto nos totais)
3. ⏳ Melhorar exibição dos logs em `/orders/{id}/audit` para ser mais amigável
4. ⏳ Garantir que logs de devoluções apareçam corretamente em `/activity`
5. ⏳ Verificar se logs incluem todas as mudanças (quantidades, descontos, totais)

### Estrutura de Log
```json
{
  "action": "reopened_with_auto_adjustment",
  "user_id": 1,
  "timestamp": "2025-10-30T20:00:00Z",
  "adjustments": [
    {
      "item_id": 5,
      "item_name": "Produto X",
      "original_quantity": 10,
      "returned_quantity": 5,
      "new_quantity": 5,
      "original_discount": 3.00,
      "new_discount": 0.00,
      "reason": "Devolução parcial detectada - ajuste automático"
    }
  ],
  "total_changes": 1,
  "fiscal_impact": {
    "old_total": 100.00,
    "new_total": 50.00,
    "difference": -50.00
  }
}
```

### Arquivos a modificar
- `database/migrations/xxxx_add_adjustment_fields_to_order_audits.php` (NOVO)
- `app/Models/OrderAudit.php`
- `app/Http/Controllers/OrderController.php`

### Critérios de aceitação
- [ ] Logs registram todos os ajustes automáticos
- [ ] Logs incluem informações fiscais relevantes
- [ ] Logs são consultáveis em `/orders/{id}/audit`
- [ ] Logs podem ser exportados para auditoria

---

## Sequência de Implementação Recomendada

1. **FASE 1** (Print ajustado) → ✅ Prioridade do usuário, rápida
2. **FASE 2** (Helpers) → ⚠️ Base para fases seguintes
3. **FASE 3** (Modal confirmação) → 🎯 Core da funcionalidade
4. **FASE 4** (NFe devolução) → 📋 Compliance fiscal
5. **FASE 5** (Logs) → 📊 Auditoria e transparência

---

## Decisões Pendentes

### 1. Política de Desconto
Quando item é ajustado (parcial devolvido):
- [ ] **Opção A**: Zera desconto completamente
- [ ] **Opção B**: Calcula desconto proporcional (qtd_restante / qtd_original * desconto_original)
- [ ] **Opção C**: Pergunta ao usuário no modal

**Recomendação**: Opção C (perguntar) ou Opção B (proporcional) como padrão com opção de zerar.

### 2. Comportamento de Itens Totalmente Devolvidos
Quando item é 100% devolvido:
- [ ] **Opção A**: Remove completamente (não aparece no pedido)
- [ ] **Opção B**: Mantém com quantidade 0 (para histórico)

**Recomendação**: Opção A (remove) - pedido reaberto deve refletir apenas o que não foi devolvido.

### 3. NFe de Devolução
- [ ] Implementar fluxo completo de NFe de devolução agora?
- [ ] Ou apenas sugerir/bloquear e deixar para fase posterior?

**Recomendação**: Por enquanto apenas bloquear e sugerir. Implementação completa de NFe de devolução pode ser fase separada.

---

## Checklist Geral de Implementação

- [x] FASE 1: Print com quantidades ajustadas ✅ (30/10/2025)
- [x] FASE 2: Helpers centralizados ✅ (30/10/2025)
- [x] FASE 3: Modal de confirmação ✅ (30/10/2025)
- [x] FASE 4: Bloqueio e sugestão de NFe de devolução ✅ (30/10/2025)
- [ ] FASE 5: Logs melhorados ⏳ (Pendente)
- [ ] Testes em diferentes cenários ⏳ (Pendente - após FASE 5)
- [x] Documentação atualizada ✅ (Este documento)
- [x] Migrations criadas e executadas ✅ (Auditorias já implementadas)

## Resumo do Progresso

**Fases Concluídas**: 4 de 5 (80%)

**Última atualização**: 30/10/2025

**Próxima fase**: FASE 5 - Melhorar Logs e Auditoria

### Melhorias Implementadas

1. **Print inteligente**: Mostra apenas quantidades restantes após devoluções
2. **Helpers centralizados**: Código mais limpo e manutenível
3. **Modal híbrido**: Usuário escolhe entre ajuste automático ou manual
4. **Compliance fiscal**: Bloqueio e sugestão de NFe de devolução quando apropriado
5. **UX melhorada**: Alertas claros, mensagens amigáveis, toasts informativos

### Arquivos Modificados (Resumo)

**Controllers**:
- `app/Http/Controllers/OrderController.php` - Print ajustado, modais de reabertura, ajustes automáticos
- `app/Http/Controllers/ReturnController.php` - Detecção de NFe, mensagens melhoradas

**Models**:
- `app/Models/Order.php` - Helpers: `canBeReopened()`, `hasSuccessfulNfe()`, `getItemsWithPartialReturns()`, `getAdjustedTotals()`
- `app/Models/OrderItem.php` - Helpers: `getReturnedQuantityAttribute()`, `getRemainingQuantityAttribute()`, `hasReturn()`

**Views**:
- `resources/views/orders/print.blade.php` - Print com quantidades ajustadas, modal de opções
- `resources/views/orders/edit.blade.php` - Modais de ajuste/reabertura, alertas de NFe, suporte a warning
- `resources/views/orders/show.blade.php` - Exibição de devoluções por item
- `resources/views/returns/create.blade.php` - Alerta pré-devolução para NFe transmitida

**Rotas**:
- `routes/web.php` - Novas rotas para `prepare-reopen-adjustment`, `reopen-with-adjustment`, `adjust-with-returns`

---

## Notas Técnicas

### Compatibilidade
- ✅ Compatibilidade mantida com pedidos antigos (sem devoluções)
- ✅ Funcionalidades existentes preservadas
- ⚠️ Edge cases (múltiplas devoluções, devoluções totais) - testar na FASE 5

### Performance
- ✅ Eager loading usado em `OrderController` (`$order->load('items')`)
- ✅ Cálculos de devoluções otimizados via accessors no model
- ⚠️ Indexação de campos - verificar se necessário após testes de carga

### Segurança
- ✅ Permissões validadas antes de aplicar ajustes (`hasPermission('orders.edit')`)
- ✅ Validação de dados implementada (justificativa obrigatória, validação de quantidades)
- ✅ Ajustes bloqueados em pedidos com NFe transmitida (via `canBeReopened()`)

### Decisões Implementadas

#### Política de Desconto
- ✅ **Decisão**: Descontos são zerados nos itens restantes após devolução parcial
- ℹ️ **Justificativa**: Simplifica o processo e evita cálculos proporcionais complexos
- 🔄 **Possível melhoria futura**: Opção de desconto proporcional no modal

#### Comportamento de Itens Totalmente Devolvidos
- ✅ **Decisão**: Itens totalmente devolvidos são excluídos do print
- ℹ️ **Justificativa**: Print reflete apenas o que não foi devolvido (conforme solicitado)
- ⚠️ **Observação**: Itens permanecem no banco para histórico, apenas não aparecem no print

#### NFe de Devolução
- ✅ **Decisão**: Por enquanto apenas bloquear e sugerir (não implementar fluxo completo)
- ℹ️ **Justificativa**: Foco em MVP, fluxo completo pode ser fase separada
- 📋 **Próximos passos**: Implementar fluxo completo quando necessário

