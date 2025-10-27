# ERP QFiscal - Manual do Usuário

## 📋 Índice

- [PAGAMENTOS](#pagamentos)
  - [Visão Geral](#visão-geral)
  - [Como Acessar](#como-acessar)
  - [Criando uma Nova Conta a Pagar](#criando-uma-nova-conta-a-pagar)
  - [Visualizando Contas a Pagar](#visualizando-contas-a-pagar)
  - [Filtros e Buscas](#filtros-e-buscas)
  - [Ações Disponíveis](#ações-disponíveis)
  - [Status das Contas](#status-das-contas)
  - [Auditoria Completa](#auditoria-completa)
  - [Dicas Importantes](#dicas-importantes)

- [RECEBIMENTOS](#recebimentos)
  - [Visão Geral](#visão-geral-1)
  - [Como Acessar](#como-acessar-1)
  - [Criando um Novo Recebimento](#criando-um-novo-recebimento)
  - [Visualizando Recebimentos](#visualizando-recebimentos)
  - [Filtros e Buscas](#filtros-e-buscas-1)
  - [Ações Disponíveis](#ações-disponíveis-1)
  - [Baixa em Lote](#baixa-em-lote)
  - [Status dos Recebimentos](#status-dos-recebimentos)
  - [Auditoria Completa](#auditoria-completa-1)
  - [Dicas Importantes](#dicas-importantes-1)

- [ORDENS DE SERVIÇO](#ordens-de-serviço)
  - [Visão Geral](#visão-geral-2)
  - [Como Acessar](#como-acessar-2)
  - [Criando uma Nova OS](#criando-uma-nova-os)
  - [Visualizando Ordens de Serviço](#visualizando-ordens-de-serviço)
  - [Filtros e Buscas](#filtros-e-buscas-2)
  - [Ações Disponíveis](#ações-disponíveis-2)
  - [Status das OS](#status-das-os)
  - [Fluxo Completo de uma OS](#fluxo-completo-de-uma-os)
  - [Sistema de Garantia](#sistema-de-garantia)
  - [Gestão de Itens e Produtos](#gestão-de-itens-e-produtos)
  - [Sistema de Pagamentos](#sistema-de-pagamentos)
  - [Anexos e Fotos](#anexos-e-fotos)
  - [Ocorrências e Timeline](#ocorrências-e-timeline)
  - [Cancelamento de OS](#cancelamento-de-os)
  - [Auditoria Completa](#auditoria-completa-2)
  - [Dicas Importantes](#dicas-importantes-2)

---

## PAGAMENTOS

### Visão Geral

O módulo de **Pagamentos** do ERP QFiscal é um sistema completo para gestão de contas a pagar, oferecendo controle total sobre despesas, fornecedores e fluxo de caixa. O sistema foi desenvolvido seguindo padrões profissionais de ERP com auditoria completa e rastreabilidade de todas as operações.

**Características principais:**
- ✅ **Auditoria Completa**: Todas as ações são registradas com usuário e timestamp
- ✅ **Multi-tenant**: Isolamento completo entre empresas
- ✅ **Controle de Permissões**: Acesso baseado em permissões específicas
- ✅ **Interface Intuitiva**: Design moderno e responsivo
- ✅ **Filtros Avançados**: Múltiplas opções de busca e organização
- ✅ **Gestão de Status**: Controle completo do ciclo de vida das contas

### Como Acessar

1. **Menu Principal**: Acesse o menu lateral e clique em "Contas a Pagar"
2. **URL Direta**: `/payables`
3. **Permissões Necessárias**: `payables.view`

---

## Criando uma Nova Conta a Pagar

### Passo a Passo

1. **Acesse a página de criação**:
   - Clique no botão **"Nova Conta"** (verde) no canto superior direito
   - Ou acesse diretamente `/payables/create`

2. **Preencha as informações do fornecedor**:
   
   **Opção A - Fornecedor Cadastrado:**
   - Selecione um fornecedor da lista dropdown
   - O campo "Ou digite o fornecedor (avulso)" será automaticamente desabilitado
   
   **Opção B - Fornecedor Avulso:**
   - Deixe o campo "Fornecedor Cadastrado" vazio
   - Digite o nome do fornecedor no campo "Ou digite o fornecedor (avulso)"

3. **Informações Gerais**:
   - **Descrição** (obrigatório): Descreva a natureza da despesa
   - **Número do Documento** (opcional): Número da nota fiscal, boleto, etc.

4. **Valores e Datas**:
   - **Valor** (obrigatório): Valor da conta em reais
   - **Data de Vencimento** (obrigatório): Data limite para pagamento
   - **Forma de Pagamento** (opcional): Dinheiro, Cartão, PIX

5. **Salvar**:
   - Clique em **"Salvar Conta a Pagar"**
   - A conta será criada com status "Em aberto"

### ⚠️ Avisos Importantes

- **Data Passada**: Se você selecionar uma data anterior à atual, aparecerá um aviso amarelo indicando que a conta será marcada como vencida
- **Validação**: Todos os campos obrigatórios devem ser preenchidos
- **Auditoria**: O sistema registra automaticamente quem criou a conta e quando

---

## Visualizando Contas a Pagar

### Interface Principal

A tela principal (`/payables`) exibe uma tabela com todas as contas a pagar organizadas por colunas:

| Coluna | Descrição |
|--------|-----------|
| **Fornecedor** | Nome do fornecedor com indicadores visuais |
| **Descrição** | Descrição da despesa |
| **Vencimento** | Data de vencimento com alertas visuais |
| **Valor** | Valor da conta formatado em reais |
| **Status** | Status atual com cores distintivas |
| **Ações** | Botões de ação disponíveis |

### Indicadores Visuais

**🔴 Contas Vencidas:**
- Fundo vermelho claro
- Ícone de alerta ao lado do fornecedor
- Ícone de calendário ao lado da data
- Texto em vermelho

**🟠 Contas Estornadas:**
- Fundo laranja claro
- Ícone de seta de retorno
- Texto em laranja

**⚫ Contas Canceladas:**
- Fundo cinza claro
- Ícone de X
- Texto em cinza

**🟡 Contas em Aberto:**
- Fundo branco (normal)
- Sem indicadores especiais

### Resumo Financeiro

No topo da página, você encontra três cartões com totais:

- **🟡 Em aberto**: Soma de todas as contas não pagas
- **🔴 Vencido**: Soma de contas em atraso
- **🟢 Pago**: Soma de todas as contas pagas

---

## Filtros e Buscas

### Filtros Rápidos

**Botões de acesso rápido:**
- **Hoje**: Contas que vencem hoje
- **Esta semana**: Contas que vencem na semana atual
- **Vencidos**: Contas em atraso

### Filtros Avançados

**Formulário de filtros:**

1. **Status**: Filtrar por status específico
   - Todos
   - Em aberto
   - Pago
   - Cancelado
   - Estornado

2. **Somente vencidos**: Checkbox para mostrar apenas contas em atraso

3. **Período**:
   - **Data de**: Data inicial do período
   - **Data até**: Data final do período

4. **Ordenação**:
   - **Ordenar por**: Vencimento, Valor, Cadastro
   - **Direção**: Crescente ou Decrescente

5. **Paginação**:
   - **Mostrar**: 10, 12, 25, 50, 100 ou 200 registros por página

### Como Usar os Filtros

1. **Selecione os filtros desejados**
2. **Clique em "Filtrar"** para aplicar
3. **Clique em "Limpar"** para remover todos os filtros

---

## Ações Disponíveis

### 👁️ Visualizar

**Quando aparece**: Sempre disponível para todas as contas

**O que faz**: Abre uma página detalhada com:
- Todos os dados da conta
- Histórico completo de auditoria
- Ações disponíveis para a conta

**Como usar**: Clique no ícone do olho (👁️) na coluna "Ações"

### ✏️ Editar

**Quando aparece**: 
- Contas com status "Em aberto"
- Usuário com permissão `payables.edit`

**O que faz**: Permite alterar todos os dados da conta

**Restrições**: 
- Contas pagas não podem ser editadas
- Use a função de estorno para pagamentos já realizados

### ✅ Pagar

**Quando aparece**: 
- Contas com status "Em aberto"
- Usuário com permissão `payables.pay`

**O que faz**: 
- Marca a conta como paga
- Registra data/hora do pagamento
- Registra quem realizou o pagamento

**Confirmação**: Sistema solicita confirmação antes de processar

### 🔄 Estornar

**Quando aparece**: 
- Contas com status "Pago"
- Usuário com permissão `payables.create`
- Não é estorno automático

**O que faz**: 
- Cria um estorno manual
- Registra motivo do estorno
- Mantém histórico completo
- Cria entrada negativa no caixa

**Processo**:
1. Clique no ícone de estorno (🔄)
2. Preencha o motivo (mínimo 10 caracteres)
3. Confirme o estorno

### ❌ Cancelar

**Quando aparece**: 
- Contas com status "Em aberto"
- Usuário com permissão `payables.edit`

**O que faz**: 
- Cancela a conta sem pagamento
- Registra motivo do cancelamento
- Mantém histórico para auditoria

**Processo**:
1. Clique no ícone de cancelamento (❌)
2. Preencha o motivo (mínimo 10 caracteres)
3. Confirme o cancelamento

---

## Status das Contas

### 🟡 Em Aberto
- **Cor**: Amarelo
- **Significado**: Conta criada, aguardando pagamento
- **Ações**: Editar, Pagar, Cancelar, Visualizar

### 🟢 Pago
- **Cor**: Verde
- **Significado**: Conta foi paga
- **Ações**: Estornar, Visualizar
- **Restrições**: Não pode ser editada

### 🔴 Cancelado
- **Cor**: Vermelho
- **Significado**: Conta foi cancelada (não será paga)
- **Ações**: Apenas Visualizar
- **Auditoria**: Motivo do cancelamento registrado

### 🟠 Estornado
- **Cor**: Laranja
- **Significado**: Pagamento foi estornado
- **Ações**: Apenas Visualizar
- **Auditoria**: Motivo do estorno registrado

---

## Auditoria Completa

### O que é Registrado

O sistema registra **todas** as operações com:

- **👤 Usuário**: Quem realizou a ação
- **📅 Data/Hora**: Quando a ação foi realizada
- **📝 Motivo**: Justificativa para ações críticas

### Campos de Auditoria

**Criação:**
- `created_at`: Data/hora de criação
- `created_by`: Usuário que criou

**Atualização:**
- `updated_at`: Data/hora da última atualização
- `updated_by`: Usuário que atualizou

**Pagamento:**
- `paid_at`: Data/hora do pagamento
- `paid_by`: Usuário que marcou como pago

**Estorno:**
- `reversed_at`: Data/hora do estorno
- `reversed_by`: Usuário que estornou
- `reverse_reason`: Motivo do estorno

**Cancelamento:**
- `canceled_at`: Data/hora do cancelamento
- `canceled_by`: Usuário que cancelou
- `cancel_reason`: Motivo do cancelamento

### Como Visualizar a Auditoria

1. **Clique no ícone de visualizar** (👁️) de qualquer conta
2. **Na página de detalhes**, role até a seção **"Auditoria"**
3. **Visualize todas as informações** de rastreamento

---

## Dicas Importantes

### ✅ Boas Práticas

1. **Sempre preencha o motivo** ao cancelar ou estornar contas
2. **Use fornecedores cadastrados** quando possível para melhor organização
3. **Verifique a data de vencimento** antes de salvar
4. **Confirme ações críticas** quando solicitado pelo sistema

### ⚠️ Cuidados Especiais

1. **Contas pagas não podem ser editadas** - use estorno se necessário
2. **Estornos criam entradas negativas** no caixa
3. **Cancelamentos são irreversíveis** - pense bem antes de cancelar
4. **Todas as ações são auditadas** - seja responsável

### 🔒 Segurança

1. **Permissões**: Cada usuário só vê ações permitidas
2. **Multi-tenant**: Dados isolados por empresa
3. **Confirmações**: Sistema solicita confirmação para ações críticas
4. **Histórico**: Nada é perdido - tudo fica registrado

### 📊 Relatórios e Análises

- **Use os filtros** para análises específicas
- **Monitore contas vencidas** regularmente
- **Acompanhe o fluxo de caixa** através dos totais
- **Revise a auditoria** para controle interno

---

## RECEBIMENTOS

### Visão Geral

O módulo de **Recebimentos** do ERP QFiscal é um sistema completo para gestão de contas a receber, oferecendo controle total sobre receitas, clientes e fluxo de caixa. O sistema foi desenvolvido seguindo padrões profissionais de ERP com auditoria completa e rastreabilidade de todas as operações.

**Características principais:**
- ✅ **Auditoria Completa**: Todas as ações são registradas com usuário e timestamp
- ✅ **Multi-tenant**: Isolamento completo entre empresas
- ✅ **Controle de Permissões**: Acesso baseado em permissões específicas
- ✅ **Interface Intuitiva**: Design moderno e responsivo
- ✅ **Filtros Avançados**: Múltiplas opções de busca e organização
- ✅ **Baixa em Lote**: Recebimento múltiplo de títulos
- ✅ **Gestão de Status**: Controle completo do ciclo de vida dos recebimentos
- ✅ **Integração com Pedidos**: Recebimentos automáticos de vendas

### Como Acessar

1. **Menu Principal**: Acesse o menu lateral e clique em "Contas a Receber"
2. **URL Direta**: `/receivables`
3. **Permissões Necessárias**: `receivables.view`

---

## Criando um Novo Recebimento

### Passo a Passo

1. **Acesse a página de criação**:
   - Clique no botão **"Novo Recebimento"** (verde) no canto superior direito
   - Ou acesse diretamente `/receivables/create`

2. **Preencha as informações do cliente**:
   
   **Opção A - Cliente Cadastrado:**
   - Selecione um cliente da lista dropdown
   
   **Opção B - Cliente Avulso:**
   - Deixe o campo "Cliente Cadastrado" vazio
   - O sistema registrará como recebimento manual

3. **Informações Gerais**:
   - **Descrição** (obrigatório): Descreva a natureza da receita
   - **Número do Documento** (opcional): Número da nota fiscal, recibo, etc.
   - **Forma de Pagamento** (opcional): Dinheiro, PIX, Cartão, Boleto

4. **Valores e Datas**:
   - **Valor** (obrigatório): Valor da receita em reais
   - **Data de Vencimento** (obrigatório): Data limite para recebimento

5. **Salvar**:
   - Clique em **"Salvar Conta a Receber"**
   - O recebimento será criado com status "Em aberto"

### ⚠️ Avisos Importantes

- **Data Passada**: Se você selecionar uma data anterior à atual, aparecerá um aviso amarelo indicando que o recebimento será marcado como vencido
- **Validação**: Todos os campos obrigatórios devem ser preenchidos
- **Auditoria**: O sistema registra automaticamente quem criou o recebimento e quando

---

## Visualizando Recebimentos

### Interface Principal

A tela principal (`/receivables`) exibe uma tabela com todos os recebimentos organizados por colunas:

| Coluna | Descrição |
|--------|-----------|
| **Checkbox** | Seleção para baixa em lote (apenas recebimentos em aberto) |
| **Descrição** | Descrição da receita com indicadores visuais |
| **Cliente** | Nome do cliente com indicadores de origem |
| **Vencimento** | Data de vencimento com alertas visuais |
| **Valor** | Valor da receita formatado em reais |
| **Status** | Status atual com cores distintivas |
| **Ações** | Botões de ação disponíveis |

### Indicadores Visuais

**🔴 Recebimentos Vencidos:**
- Fundo vermelho claro
- Ícone de alerta ao lado da descrição
- Ícone de calendário ao lado da data
- Texto em vermelho

**🟠 Recebimentos Estornados:**
- Fundo laranja claro
- Ícone de seta de retorno
- Texto em laranja

**⚫ Recebimentos Cancelados:**
- Fundo cinza claro
- Ícone de X
- Texto em cinza

**🟡 Recebimentos em Aberto:**
- Fundo branco (normal)
- Sem indicadores especiais

**📋 Recebimentos Vinculados a Pedidos:**
- Ícone de documento ao lado do cliente
- Texto "Vinculado a pedido"
- Ações limitadas (não podem ser editados/cancelados diretamente)

### Resumo Financeiro

No topo da página, você encontra três cartões com totais:

- **🟡 Em aberto**: Soma de todos os recebimentos não pagos
- **🔴 Vencido**: Soma de recebimentos em atraso
- **🟢 Pago**: Soma de todos os recebimentos pagos

---

## Filtros e Buscas

### Filtros Rápidos

**Botões de acesso rápido:**
- **Hoje**: Recebimentos que vencem hoje
- **Esta semana**: Recebimentos que vencem na semana atual
- **Vencidos**: Recebimentos em atraso

### Filtros Avançados

**Formulário de filtros:**

1. **Status**: Filtrar por status específico
   - Todos
   - Em aberto
   - Pago
   - Estornado

2. **Somente vencidos**: Checkbox para mostrar apenas recebimentos em atraso

3. **Período**:
   - **Data de**: Data inicial do período
   - **Data até**: Data final do período

4. **Ordenação**:
   - **Ordenar por**: Vencimento, Valor, Cadastro
   - **Direção**: Crescente ou Decrescente

5. **Paginação**:
   - **Mostrar**: 10, 12, 25, 50, 100 ou 200 registros por página

### Como Usar os Filtros

1. **Selecione os filtros desejados**
2. **Clique em "Filtrar"** para aplicar
3. **Clique em "Limpar"** para remover todos os filtros

---

## Ações Disponíveis

### 👁️ Visualizar

**Quando aparece**: Sempre disponível para todos os recebimentos

**O que faz**: Abre uma página detalhada com:
- Todos os dados do recebimento
- Histórico completo de auditoria
- Ações disponíveis para o recebimento

**Como usar**: Clique no ícone do olho (👁️) na coluna "Ações"

### ✏️ Editar

**Quando aparece**: 
- Recebimentos com status "Em aberto"
- Usuário com permissão `receivables.edit`
- **NÃO** aparece para recebimentos vinculados a pedidos

**O que faz**: Permite alterar todos os dados do recebimento

**Restrições**: 
- Recebimentos pagos não podem ser editados
- Recebimentos de pedidos não podem ser editados diretamente
- Use a função de estorno para recebimentos já realizados

### ✅ Receber

**Quando aparece**: 
- Recebimentos com status "Em aberto"
- Usuário com permissão `receivables.receive`

**O que faz**: 
- Marca o recebimento como pago
- Registra data/hora do recebimento
- Registra quem realizou o recebimento

**Confirmação**: Sistema solicita confirmação antes de processar

### 🔄 Estornar

**Quando aparece**: 
- Recebimentos com status "Pago"
- Usuário com permissão `receivables.create`
- **NÃO** aparece para recebimentos vinculados a pedidos
- Não é estorno automático

**O que faz**: 
- Cria um estorno manual
- Registra motivo do estorno
- Mantém histórico completo
- Cria entrada negativa no caixa

**Processo**:
1. Clique no ícone de estorno (🔄)
2. Preencha o motivo (mínimo 10 caracteres)
3. Confirme o estorno

### ❌ Cancelar

**Quando aparece**: 
- Recebimentos com status "Em aberto"
- Usuário com permissão `receivables.delete`
- **NÃO** aparece para recebimentos vinculados a pedidos

**O que faz**: 
- Cancela o recebimento sem pagamento
- Registra motivo do cancelamento
- Mantém histórico para auditoria

**Processo**:
1. Clique no ícone de cancelamento (❌)
2. Preencha o motivo (mínimo 10 caracteres)
3. Confirme o cancelamento

### 📄 Emitir Boleto

**Quando aparece**: 
- Recebimentos com status "Em aberto"
- Usuário com permissão `receivables.receive`
- Plano permite emissão de boletos

**O que faz**: 
- Emite boleto bancário via Mercado Pago
- Envia por e-mail para o cliente (opcional)
- Registra dados do boleto no sistema

**Processo**:
1. Clique no ícone de boleto (📄)
2. Configure vencimento, multa e juros
3. Escolha se deseja enviar por e-mail
4. Confirme a emissão

---

## Baixa em Lote

### Como Funciona

A **Baixa em Lote** permite receber múltiplos títulos de uma só vez, economizando tempo e reduzindo erros.

### Passo a Passo

1. **Selecione os recebimentos**:
   - Marque os checkboxes dos recebimentos em aberto que deseja receber
   - Use o checkbox do cabeçalho para selecionar todos da página

2. **Abra o modal de baixa em lote**:
   - Clique no botão **"Baixar selecionados"** (verde)
   - O modal será aberto automaticamente

3. **Configure os dados**:
   - **Data do Recebimento**: Data/hora do recebimento (padrão: agora)
   - **Forma de Pagamento**: Método usado para todos os títulos
   - **Taxa**: Valor de taxa cobrada (opcional)
   - **Descrição da Taxa**: Descrição da taxa cobrada (opcional)

4. **Confirme a baixa**:
   - Clique em **"Confirmar Baixa"**
   - Todos os títulos selecionados serão marcados como pagos

### ⚠️ Importante

- **Apenas recebimentos em aberto** podem ser selecionados
- **Recebimentos de pedidos** não aparecem para seleção
- **Todos os títulos** receberão a mesma data e forma de pagamento
- **Auditoria completa** é registrada para cada título

---

## Status dos Recebimentos

### 🟡 Em Aberto
- **Cor**: Amarelo
- **Significado**: Recebimento criado, aguardando pagamento
- **Ações**: Editar, Receber, Cancelar, Visualizar, Emitir Boleto
- **Seleção**: Disponível para baixa em lote

### 🟢 Pago
- **Cor**: Verde
- **Significado**: Recebimento foi quitado
- **Ações**: Estornar, Visualizar
- **Restrições**: Não pode ser editado

### 🔴 Cancelado
- **Cor**: Vermelho
- **Significado**: Recebimento foi cancelado (não será recebido)
- **Ações**: Apenas Visualizar
- **Auditoria**: Motivo do cancelamento registrado

### 🟠 Estornado
- **Cor**: Laranja
- **Significado**: Recebimento foi estornado
- **Ações**: Apenas Visualizar
- **Auditoria**: Motivo do estorno registrado

---

## Auditoria Completa

### O que é Registrado

O sistema registra **todas** as operações com:

- **👤 Usuário**: Quem realizou a ação
- **📅 Data/Hora**: Quando a ação foi realizada
- **📝 Motivo**: Justificativa para ações críticas

### Campos de Auditoria

**Criação:**
- `created_at`: Data/hora de criação
- `created_by`: Usuário que criou

**Atualização:**
- `updated_at`: Data/hora da última atualização
- `updated_by`: Usuário que atualizou

**Recebimento:**
- `received_at`: Data/hora do recebimento
- `received_by`: Usuário que marcou como recebido

**Estorno:**
- `reversed_at`: Data/hora do estorno
- `reversed_by`: Usuário que estornou
- `reverse_reason`: Motivo do estorno

**Cancelamento:**
- `canceled_at`: Data/hora do cancelamento
- `canceled_by`: Usuário que cancelou
- `cancel_reason`: Motivo do cancelamento

### Como Visualizar a Auditoria

1. **Clique no ícone de visualizar** (👁️) de qualquer recebimento
2. **Na página de detalhes**, role até a seção **"Auditoria"**
3. **Visualize todas as informações** de rastreamento

---

## Dicas Importantes

### ✅ Boas Práticas

1. **Sempre preencha o motivo** ao cancelar ou estornar recebimentos
2. **Use clientes cadastrados** quando possível para melhor organização
3. **Verifique a data de vencimento** antes de salvar
4. **Confirme ações críticas** quando solicitado pelo sistema
5. **Use baixa em lote** para receber múltiplos títulos rapidamente

### ⚠️ Cuidados Especiais

1. **Recebimentos pagos não podem ser editados** - use estorno se necessário
2. **Recebimentos de pedidos** devem ser gerenciados no módulo de pedidos
3. **Estornos criam entradas negativas** no caixa
4. **Cancelamentos são irreversíveis** - pense bem antes de cancelar
5. **Todas as ações são auditadas** - seja responsável

### 🔒 Segurança

1. **Permissões**: Cada usuário só vê ações permitidas
2. **Multi-tenant**: Dados isolados por empresa
3. **Confirmações**: Sistema solicita confirmação para ações críticas
4. **Histórico**: Nada é perdido - tudo fica registrado
5. **Origem**: Recebimentos de pedidos têm proteções especiais

### 📊 Relatórios e Análises

- **Use os filtros** para análises específicas
- **Monitore recebimentos vencidos** regularmente
- **Acompanhe o fluxo de caixa** através dos totais
- **Revise a auditoria** para controle interno
- **Use baixa em lote** para otimizar processos

### 🎯 Diferenças dos Pagamentos

- **Cores**: Interface verde (vs. vermelha dos pagamentos)
- **Baixa em Lote**: Funcionalidade exclusiva dos recebimentos
- **Emissão de Boletos**: Integração com Mercado Pago
- **Vinculação com Pedidos**: Recebimentos automáticos de vendas
- **Proteções Especiais**: Recebimentos de pedidos têm ações limitadas

---

## ORDENS DE SERVIÇO

### Visão Geral

O módulo de **Ordens de Serviço** do ERP QFiscal é um sistema completo para gestão de assistência técnica, oferecendo controle total sobre serviços, equipamentos, garantias e fluxo de trabalho. O sistema foi desenvolvido seguindo padrões profissionais de ERP com auditoria completa e rastreabilidade de todas as operações.

**Características principais:**
- ✅ **Auditoria Completa**: Todas as ações são registradas com usuário e timestamp
- ✅ **Multi-tenant**: Isolamento completo entre empresas
- ✅ **Controle de Permissões**: Acesso baseado em permissões específicas
- ✅ **Interface Intuitiva**: Design moderno e responsivo
- ✅ **Filtros Avançados**: Múltiplas opções de busca e organização
- ✅ **Sistema de Garantia**: Controle completo de garantias e reincidências
- ✅ **Gestão de Estoque**: Integração automática com produtos
- ✅ **Sistema de Pagamentos**: Integração com recebíveis e caixa
- ✅ **Timeline Completa**: Histórico detalhado de todas as ações
- ✅ **Anexos e Fotos**: Upload de documentos e imagens
- ✅ **Cancelamento Inteligente**: Reversão automática de impactos

### Como Acessar

1. **Menu Principal**: Acesse o menu lateral e clique em "Ordens de Serviço"
2. **URL Direta**: `/service_orders`
3. **Permissões Necessárias**: `service_orders.view`

---

## Criando uma Nova OS

### Passo a Passo

1. **Acesse a página de criação**:
   - Clique no botão **"Nova OS"** (verde) no canto superior direito
   - Ou acesse diretamente `/service_orders/create`

2. **Preencha as informações básicas**:
   - **Cliente** (obrigatório): Selecione um cliente cadastrado
   - **Título** (obrigatório): Descrição resumida do serviço
   - **Descrição** (obrigatório): Detalhes do serviço a ser realizado

3. **Informações do Equipamento**:
   - **Marca**: Marca do equipamento
   - **Modelo**: Modelo do equipamento
   - **Número de Série**: Número de série (importante para garantia)
   - **Descrição do Equipamento**: Características físicas
   - **Defeito Relatado**: Problema descrito pelo cliente

4. **Informações Técnicas**:
   - **Técnico Responsável**: Usuário que irá executar o serviço
   - **Notas Internas**: Observações para a equipe técnica
   - **Status**: Status inicial (padrão: "Em análise")

5. **Salvar**:
   - Clique em **"Salvar OS"**
   - A OS será criada com status "Em análise"

### ⚠️ Avisos Importantes

- **Número de Série**: Fundamental para controle de garantia e reincidências
- **Validação**: Todos os campos obrigatórios devem ser preenchidos
- **Auditoria**: O sistema registra automaticamente quem criou a OS e quando
- **Numeração**: O sistema gera automaticamente o número da OS

---

## Visualizando Ordens de Serviço

### Interface Principal

A tela principal (`/service_orders`) exibe uma tabela com todas as OS organizadas por colunas:

| Coluna | Descrição |
|--------|-----------|
| **Número** | Número da OS com link para visualização |
| **Cliente** | Nome do cliente |
| **Equipamento** | Marca e modelo do equipamento |
| **Técnico** | Técnico responsável |
| **Fotos** | Quantidade de anexos/fotos |
| **Status** | Status atual com cores distintivas |
| **Ações** | Botões de ação disponíveis |

### Indicadores Visuais

**🟡 Em Análise:**
- Fundo amarelo
- Significado: OS recém-criada, aguardando análise

**🔵 Orçada:**
- Fundo azul
- Significado: Orçamento realizado, aguardando aprovação
- Badge "Avisar Cliente" se não notificado
- Badge "Cliente Avisado" se notificado
- Badge "Aprovada" se aprovada pelo cliente

**🟣 Em Andamento:**
- Fundo roxo
- Significado: Serviço em execução

**🟦 Serviço Finalizado:**
- Fundo índigo
- Significado: Serviço concluído, aguardando finalização

**🟠 Garantia:**
- Fundo laranja
- Significado: OS de garantia (valor zero)

**⚫ Sem Reparo:**
- Fundo cinza escuro
- Significado: Cliente desistiu do reparo

**🟢 Finalizada:**
- Fundo verde
- Significado: OS completamente finalizada

**🔴 Cancelada:**
- Fundo vermelho
- Significado: OS cancelada

### Resumo por Status

No topo da página, você encontra cartões com totais por status:
- **Total de OS**: Soma de todas as OS
- **Em Análise**: OS aguardando análise
- **Orçadas**: OS com orçamento pronto
- **Em Andamento**: OS em execução
- **Finalizadas**: OS concluídas

---

## Filtros e Buscas

### Filtros Rápidos

**Botões de acesso rápido:**
- **Hoje**: OS criadas hoje
- **Esta semana**: OS criadas na semana atual
- **Em andamento**: OS em execução

### Filtros Avançados

**Formulário de filtros:**

1. **Status**: Filtrar por status específico
   - Todos
   - Em análise
   - Orçada
   - Em andamento
   - Serviço finalizado
   - Garantia
   - Sem reparo
   - Finalizada
   - Cancelada

2. **Cliente**: Filtrar por cliente específico

3. **Busca**: Pesquisar por número, título ou descrição

4. **Ordenação**:
   - **Ordenar por**: Data de criação, Número, Cliente
   - **Direção**: Crescente ou Decrescente

5. **Paginação**:
   - **Mostrar**: 10, 25, 50 ou 100 registros por página

### Como Usar os Filtros

1. **Selecione os filtros desejados**
2. **Clique em "Filtrar"** para aplicar
3. **Clique em "Limpar"** para remover todos os filtros

---

## Ações Disponíveis

### 👁️ Visualizar

**Quando aparece**: Sempre disponível para todas as OS

**O que faz**: Abre uma página detalhada com:
- Todos os dados da OS
- Histórico completo de auditoria
- Timeline de ocorrências
- Ações disponíveis para a OS

**Como usar**: Clique no ícone do olho (👁️) na coluna "Ações"

### ✏️ Editar

**Quando aparece**: 
- OS com status "Em análise", "Orçada", "Em andamento", "Serviço finalizado"
- Usuário com permissão `service_orders.edit`

**O que faz**: Permite alterar todos os dados da OS

**Restrições**: 
- OS finalizadas não podem ser editadas
- OS canceladas não podem ser editadas

### ✅ Finalizar

**Quando aparece**: 
- OS com status "Em andamento" ou "Garantia"
- Usuário com permissão `service_orders.finalize`

**O que faz**: 
- Abre formulário de finalização
- Permite definir dados de entrega
- Processa pagamentos automaticamente
- Calcula garantia automaticamente
- Deduz estoque de produtos utilizados

### 🖨️ Imprimir

**Quando aparece**: 
- OS com status "Finalizada"
- Usuário com permissão `service_orders.view`

**O que faz**: 
- Gera recibo de entrega
- Formato otimizado para impressão
- Inclui todos os dados da OS

### 📧 Enviar E-mail

**Quando aparece**: 
- OS com status "Orçada" ou "Serviço finalizado"
- Usuário com permissão `service_orders.edit`

**O que faz**: 
- Envia e-mail para o cliente
- Pode incluir orçamento ou recibo
- Registra envio na auditoria

### ❌ Cancelar

**Quando aparece**: 
- OS com status diferente de "Cancelada"
- Usuário com permissão `service_orders.cancel`

**O que faz**: 
- Cancela a OS com reversões automáticas
- Estorna estoque utilizado
- Cancela recebíveis pendentes
- Registra motivo do cancelamento

---

## Status das OS

### 🟡 Em Análise
- **Cor**: Amarelo
- **Significado**: OS recém-criada, aguardando análise técnica
- **Ações**: Editar, Visualizar, Cancelar
- **Próximo passo**: Realizar orçamento

### 🔵 Orçada
- **Cor**: Azul
- **Significado**: Orçamento realizado, aguardando aprovação do cliente
- **Ações**: Editar, Visualizar, Enviar E-mail, Cancelar
- **Badges**: Avisar Cliente, Cliente Avisado, Aprovada
- **Próximo passo**: Aguardar aprovação ou iniciar serviço

### 🟣 Em Andamento
- **Cor**: Roxo
- **Significado**: Serviço em execução
- **Ações**: Editar, Visualizar, Finalizar, Cancelar
- **Próximo passo**: Concluir serviço

### 🟦 Serviço Finalizado
- **Cor**: Índigo
- **Significado**: Serviço concluído, aguardando finalização
- **Ações**: Editar, Visualizar, Enviar E-mail, Cancelar
- **Próximo passo**: Finalizar OS

### 🟠 Garantia
- **Cor**: Laranja
- **Significado**: OS de garantia (valor zero)
- **Ações**: Editar, Visualizar, Finalizar, Cancelar
- **Especial**: Botões "Não é Garantia" e "Estender Garantia"

### ⚫ Sem Reparo
- **Cor**: Cinza escuro
- **Significado**: Cliente desistiu do reparo
- **Ações**: Visualizar, Cancelar
- **Próximo passo**: Finalizar ou cancelar

### 🟢 Finalizada
- **Cor**: Verde
- **Significado**: OS completamente finalizada
- **Ações**: Visualizar, Imprimir, Criar Garantia
- **Especial**: Botão "Criar Garantia" disponível

### 🔴 Cancelada
- **Cor**: Vermelho
- **Significado**: OS cancelada
- **Ações**: Visualizar, Imprimir Cancelamento
- **Auditoria**: Motivo do cancelamento registrado

---

## Fluxo Completo de uma OS

### 1. 📝 Criação
- Cliente traz equipamento
- Técnico cria OS com dados do equipamento
- Status: **Em análise**

### 2. 🔍 Análise e Orçamento
- Técnico analisa o problema
- Realiza orçamento detalhado
- Status: **Orçada**

### 3. 📧 Aprovação do Cliente
- Sistema envia e-mail com orçamento
- Cliente aprova ou rejeita
- Status: **Orçada** (com badge de aprovação)

### 4. 🔧 Execução do Serviço
- Técnico inicia reparo
- Adiciona produtos/serviços conforme necessário
- Status: **Em andamento**

### 5. ✅ Conclusão
- Serviço concluído
- Status: **Serviço finalizado**

### 6. 🏁 Finalização
- Dados de entrega preenchidos
- Pagamento processado
- Estoque deduzido automaticamente
- Garantia calculada automaticamente
- Status: **Finalizada**

### 7. 🛡️ Garantia (se necessário)
- Cliente retorna com problema
- Técnico cria OS de garantia
- Status: **Garantia**

---

## Sistema de Garantia

### Visão Geral

O sistema de garantia é uma funcionalidade avançada que permite:
- **Criação automática** de OS de garantia
- **Controle de reincidências** por número de série
- **Extensão de garantia** com justificativa
- **Alteração de status** quando não é garantia
- **Auditoria completa** de todas as ações

### Como Funciona

#### 1. **Criação de Garantia**
- Disponível apenas para OS **finalizadas**
- Botão "Criar Garantia" na OS original
- Sistema verifica se está dentro do prazo
- Cria nova OS com numeração especial (GAR2025000001)
- **Valor sempre zero** para garantias

#### 2. **Gestão de Garantia**
- **Botão "Não é Garantia"**: Altera status quando técnico descobre que não é garantia
- **Botão "Estender Garantia"**: Adiciona dias com justificativa
- **Auditoria obrigatória**: Todas as ações são registradas

#### 3. **Controle de Reincidência**
- Sistema conta automaticamente quantas vezes o mesmo equipamento (por número de série) volta para garantia
- Relatórios de produtos problemáticos
- Alertas para qualidade

### Configurações

- **Garantia Padrão**: Configurável em `/settings` (padrão: 90 dias)
- **Cálculo Automático**: Data de garantia calculada automaticamente na finalização
- **Override**: Possibilidade de alterar dias de garantia por OS (com permissão especial)

---

## Gestão de Itens e Produtos

### Adicionando Itens

#### 1. **Produtos Cadastrados**
- Selecione produto da lista
- Sistema preenche automaticamente: nome, descrição, unidade, preço
- Quantidade e desconto podem ser ajustados
- **Estoque deduzido automaticamente** na finalização

#### 2. **Serviços Avulsos**
- Digite nome do serviço
- Defina descrição, quantidade, unidade e preço
- Não afeta estoque

### Controle de Estoque

- **Dedução Automática**: Produtos são deduzidos do estoque apenas na finalização
- **Verificação de Estoque**: Sistema verifica disponibilidade antes de finalizar
- **Estoque Negativo**: Controlado por configuração em `/settings`
- **Reversão**: Em caso de cancelamento, estoque é revertido automaticamente

### Cálculo de Valores

- **Valor Unitário**: Preço por unidade
- **Desconto**: Valor ou percentual de desconto
- **Total da Linha**: Calculado automaticamente
- **Total da OS**: Soma de todos os itens

---

## Sistema de Pagamentos

### Formas de Pagamento

#### 1. **Dinheiro**
- Pagamento à vista
- Valor vai direto para o caixa do dia
- Sem parcelamento

#### 2. **Cartão**
- Parcelamento configurável (padrão: até 3x)
- Juros por parcela configurável (padrão: 0%)
- Entrada opcional (vai para caixa)
- Restante vira recebíveis

#### 3. **PIX**
- Pagamento instantâneo
- Valor vai para caixa do dia
- Sem parcelamento

#### 4. **Transferência**
- Pagamento bancário
- Valor vai para caixa do dia
- Sem parcelamento

#### 5. **Boleto Bancário**
- Pagamento futuro
- Valor vira recebível
- Sem parcelamento

#### 6. **Pagamento Misto**
- Combinação de formas
- Exemplo: R$ 100 dinheiro + R$ 200 cartão 2x
- Entrada vai para caixa, restante vira recebíveis

### Configurações

- **Máximo de Parcelas**: Configurável em `/settings`
- **Juros por Parcela**: Configurável em `/settings`
- **Integração com Caixa**: Automática
- **Integração com Recebíveis**: Automática

---

## Anexos e Fotos

### Upload de Arquivos

- **Formatos Suportados**: JPG, PNG, PDF, DOC, DOCX
- **Tamanho Máximo**: 10MB por arquivo
- **Quantidade**: Até 10 arquivos por OS
- **Armazenamento**: Seguro e organizado por tenant

### Como Usar

1. **Na criação**: Selecione arquivos no formulário
2. **Na edição**: Use botão "Adicionar Fotos" na página de visualização
3. **Visualização**: Clique nos arquivos para visualizar
4. **Download**: Arquivos podem ser baixados individualmente

### Tipos de Anexos

- **Fotos do Equipamento**: Estado inicial e final
- **Documentos**: Notas fiscais, contratos, termos
- **Laudos Técnicos**: Relatórios de análise
- **Outros**: Qualquer documento relevante

---

## Ocorrências e Timeline

### Sistema de Ocorrências

O sistema registra automaticamente todas as ações importantes:

#### **Tipos de Ocorrências**
- **Contato com Cliente**: Comunicações realizadas
- **Mudança de Status**: Alterações de status da OS
- **Nota Técnica**: Observações técnicas importantes
- **Problema na Garantia**: Questões relacionadas à garantia
- **Nota de Entrega**: Informações sobre entrega
- **Nota de Pagamento**: Questões financeiras
- **Outros**: Qualquer observação adicional

#### **Prioridades**
- **Baixa**: Informações gerais
- **Média**: Situações importantes
- **Alta**: Questões críticas
- **Urgente**: Problemas que requerem atenção imediata

### Timeline Visual

- **Cronológica**: Ordenada por data/hora
- **Cores**: Diferentes cores para tipos e prioridades
- **Usuário**: Sempre mostra quem criou a ocorrência
- **Detalhes**: Informações completas de cada ação

### Adicionando Ocorrências

1. **Clique em "Adicionar Ocorrência"** na página de visualização
2. **Selecione o tipo** de ocorrência
3. **Defina a prioridade** (Baixa, Média, Alta, Urgente)
4. **Digite a descrição** detalhada
5. **Marque "Nota Interna"** se for apenas para funcionários
6. **Salve** a ocorrência

---

## Cancelamento de OS

### Quando Cancelar

- **Cliente desistiu** do serviço
- **Equipamento não pode ser reparado**
- **Problemas de pagamento**
- **Erro na criação** da OS

### Processo de Cancelamento

#### 1. **Análise de Impactos**
O sistema calcula automaticamente:
- **Estoque**: Quais produtos serão revertidos
- **Financeiro**: Valores a serem estornados
- **Garantias**: Garantias ativas que serão canceladas

#### 2. **Confirmação**
- **Modal de confirmação** com detalhes dos impactos
- **Campo obrigatório** para motivo do cancelamento
- **Checkbox de confirmação** obrigatório

#### 3. **Reversões Automáticas**
- **Estoque**: Produtos utilizados são revertidos
- **Recebíveis**: Títulos pendentes são cancelados
- **Caixa**: Valores recebidos são estornados
- **Garantias**: Garantias ativas são canceladas

### Auditoria de Cancelamento

- **Motivo**: Registrado obrigatoriamente
- **Usuário**: Quem cancelou
- **Data/Hora**: Quando foi cancelado
- **Impactos**: Detalhes de todas as reversões
- **Irreversível**: Cancelamento não pode ser desfeito

---

## Auditoria Completa

### O que é Registrado

O sistema registra **todas** as operações com:

- **👤 Usuário**: Quem realizou a ação
- **📅 Data/Hora**: Quando a ação foi realizada
- **📝 Detalhes**: Informações específicas da ação
- **🔄 Status**: Mudanças de status com histórico

### Campos de Auditoria

**Criação:**
- `created_at`: Data/hora de criação
- `created_by`: Usuário que criou

**Atualização:**
- `updated_at`: Data/hora da última atualização
- `updated_by`: Usuário que atualizou

**Orçamento:**
- `quoted_at`: Data/hora do orçamento
- `quoted_by`: Usuário que fez o orçamento

**Finalização:**
- `finalized_at`: Data/hora da finalização
- `finalized_by`: Usuário que finalizou

**Cancelamento:**
- `cancelled_at`: Data/hora do cancelamento
- `cancelled_by`: Usuário que cancelou

**Garantia:**
- `warranty_history`: Histórico completo de garantias
- `warranty_logs`: Logs de ações de garantia

### Como Visualizar a Auditoria

1. **Clique no ícone de visualizar** (👁️) de qualquer OS
2. **Na página de detalhes**, role até a seção **"Auditoria"**
3. **Visualize todas as informações** de rastreamento
4. **Timeline completa** com todas as ações

---

## Dicas Importantes

### ✅ Boas Práticas

1. **Sempre preencha o número de série** para controle de garantia
2. **Use descrições detalhadas** para melhor rastreabilidade
3. **Adicione ocorrências** para manter histórico completo
4. **Confirme ações críticas** quando solicitado pelo sistema
5. **Mantenha fotos atualizadas** do estado do equipamento
6. **Verifique estoque** antes de finalizar OS com produtos

### ⚠️ Cuidados Especiais

1. **OS finalizadas não podem ser editadas** - use cancelamento se necessário
2. **Cancelamentos são irreversíveis** - pense bem antes de cancelar
3. **Garantias têm prazo** - sistema verifica automaticamente
4. **Estoque é deduzido automaticamente** - verifique disponibilidade
5. **Todas as ações são auditadas** - seja responsável

### 🔒 Segurança

1. **Permissões**: Cada usuário só vê ações permitidas
2. **Multi-tenant**: Dados isolados por empresa
3. **Confirmações**: Sistema solicita confirmação para ações críticas
4. **Histórico**: Nada é perdido - tudo fica registrado
5. **Reversões**: Cancelamentos revertem automaticamente impactos

### 📊 Relatórios e Análises

- **Use os filtros** para análises específicas
- **Monitore OS em atraso** regularmente
- **Acompanhe garantias** próximas do vencimento
- **Revise a auditoria** para controle interno
- **Analise reincidências** para identificar produtos problemáticos

### 🎯 Diferenças dos Outros Módulos

- **Status Complexos**: 8 status diferentes com fluxo específico
- **Sistema de Garantia**: Funcionalidade exclusiva e avançada
- **Integração Completa**: Estoque, pagamentos e recebíveis
- **Timeline Detalhada**: Histórico completo de todas as ações
- **Cancelamento Inteligente**: Reversão automática de impactos

---

*Este manual cobre todas as funcionalidades do módulo de Ordens de Serviço do ERP QFiscal. Para dúvidas específicas ou suporte técnico, consulte a documentação técnica ou entre em contato com o administrador do sistema.*
