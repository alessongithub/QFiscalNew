## Diferença entre Cancelamento e Devolução

### Cancelamento de Pedido (OrderController::destroy)
- Cancela o pedido INTEIRO
- Devolve TODO o estoque
- Estorna TODOS os pagamentos
- Cancela TODAS as parcelas em aberto
- Justificativa obrigatória (mínimo 15 caracteres)
- Prazo configurável por tenant
- Bloqueia se NF-e foi transmitida
- Taxa de antecipação para cartão

### Devolução Parcial/Total (ReturnController)
- Permite devolução de itens específicos
- Devolve estoque proporcionalmente
- 3 opções financeiras: Abatimento, Estorno, Crédito
- Compensação automática inteligente
- Pedido continua existindo
- Permite emissão de NF-e de devolução

## Regras de Negócio - Cancelamento

1. **Validação NF-e**: Pedido com NF-e transmitida não pode ser cancelado diretamente
2. **Prazo**: Configurável por tenant (padrão: 90 dias)
3. **Justificativa**: Sempre obrigatória, 15-500 caracteres
4. **Estoque**: Devolvido automaticamente
5. **Financeiro**:
   - Títulos pagos: Estorno no caixa (Payable paid)
   - Títulos em aberto: Cancelamento (status canceled)
   - Cartão: Taxa de antecipação configurável
6. **Auditoria**: Log completo com valores e motivo

## Fluxo de Devoluções (Pedidos)

### Objetivo
Padronizar o tratamento operacional, financeiro e de estoque das devoluções (totais ou parciais) de pedidos, com ou sem emissão de NF-e.

### Onde usar
- Menu: Devoluções → Selecionar Pedido → Registrar Devolução

### Passos do processo
1. Seleção do Pedido e Itens
   - Informe as quantidades a devolver por item. O sistema bloqueia devolver acima do vendido (ajustado pelo que já foi devolvido).
   - O estoque é automaticamente reabastecido com a quantidade devolvida.

2. Tratamento Financeiro
   Escolha uma das opções:
   - Abater do Contas a Receber (não altera caixa agora)
     - O sistema cria um título negativo (valor < 0) vinculado ao pedido/cliente, status `open`.
     - Aplica compensação automática nos títulos positivos em aberto do mesmo pedido (ordem: vencimento mais próximo primeiro):
       - Se o crédito cobre totalmente uma parcela, a parcela é cancelada (amount = 0, status `canceled`).
       - Se cobre parcialmente, a parcela tem o valor reduzido (status `partial`).
       - Caso sobre crédito após quitar todas as parcelas do pedido, ele permanece como título negativo em aberto para uso futuro.
   - Estornar recebimento agora (sai do caixa)
     - O sistema cria um título negativo já como `paid`, com `received_at = hoje` e `payment_method` (Dinheiro/Cartão/PIX).
     - Impacta o Caixa do Dia imediatamente (aparece como saída).
     - Não faz compensação porque já está liquidado.
   - Gerar crédito do cliente (usar em compras futuras)
     - Igual ao Abatimento, porém marcado com `payment_method = credit`.
     - Compensa automaticamente as parcelas do próprio pedido; se sobrar, fica em aberto para abater vendas futuras.

3. Documento Fiscal (quando aplicável)
   - Se houver necessidade de NF-e de devolução, emitir nota referenciando a nota original. A integração de emissão está prevista no módulo de Pedidos/OS.

### Regras e Detalhes
- Estoque: para cada item devolvido, gera-se movimento `entry` em `StockMovement` com quantidade e preço unitário do item original.
- Cálculo financeiro: `total_refund = soma(qtd_devolvida × preço_unitário_original)`.
- Amarrações:
  - Títulos criados na devolução levam `order_id` e `client_id` para rastreabilidade.
  - Compensação automática só considera títulos positivos (`amount > 0`) do mesmo `order_id` e status `open`/`partial`.

### Exemplos
- Devolução parcial R$ 200 em pedido com 2 parcelas de R$ 300:
  - Abatimento/Crédito: Parcela 1 reduz para R$ 100 (status `partial`).
  - Estorno: título negativo `paid` de R$ 200 impacta caixa; parcelas originais permanecem inalteradas.

### Limitações Atuais
- Compensação automática considera apenas títulos do mesmo pedido. Crédito remanescente fica disponível para usos futuros, mas a compensação inter-pedidos (cliente) é manual por enquanto.

### Telas afetadas
- `returns/create`: seleção do tipo financeiro (Abater, Estornar, Crédito) e meios de estorno.
- `ReturnController@store`: geração de estoque, criação do(s) título(s) e compensação automática.


