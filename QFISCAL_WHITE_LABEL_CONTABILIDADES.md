# QFiscal ERP White Label
## Sistema de Gestão Empresarial Completo para Contabilidades

---

## 📋 Sumário Executivo

O **QFiscal ERP** é uma solução completa de gestão empresarial desenvolvida com tecnologias modernas, projetada especificamente para atender escritórios de contabilidade que desejam oferecer uma plataforma de gestão integrada aos seus clientes.

Com arquitetura **multi-tenant**, o sistema permite que contabilidades operem como **white label**, oferecendo subdomínios personalizados e branding próprio para cada escritório, enquanto gerenciam múltiplas empresas-clientes de forma centralizada e isolada.

---

## 🎯 Por Que o QFiscal é Ideal para Contabilidades?

### **O Diferencial White Label**

O QFiscal não é apenas mais um ERP. É uma **plataforma preparada para ser sua** própria solução de mercado:

- **Subdomínios Personalizados**: Cada escritório opera em `suacontabilidade.qfiscal.com.br`
- **Branding Completo**: Logo, cores, tema customizáveis por parceiro
- **Comissionamento**: Sistema de repasse automático ou manual configurável
- **Marketplace**: Preparado para split automático via Mercado Pago (fase 2)
- **Multi-tenant Robusto**: Isolamento total de dados entre clientes
- **Gestão Centralizada**: Painel administrativo para gerenciar todos os tenants

---

## 💼 Dores que o QFiscal Resolve para Contabilidades

### **1. Desorganização dos Clientes**

**Dor**: Clientes mantêm controles manuais em planilhas, cadernos ou sistemas ultrapassados, dificultando a contabilidade.

**Solução QFiscal**:
- Sistema online acessível de qualquer lugar
- Interface intuitiva em português brasileiro
- Cadastros padronizados e completos
- Dados sempre atualizados e acessíveis para a contabilidade

### **2. Emissão Fiscal Problemática**

**Dor**: Clientes sem sistema fiscal adequado, gerando retrabalho e inconsistências nos dados contábeis.

**Solução QFiscal**:
- **Emissão de NF-e** integrada via componentes ACBr (padrão do mercado brasileiro)
- **Emissão de NFC-e** para PDV/varejo
- **Emissão de NFS-e** para prestadores de serviço
- Validações fiscais automáticas (NCM, CFOP, CST, CSOSN)
- Armazenamento de XMLs e DANFEs

### **3. Controle Financeiro Inexistente**

**Dor**: Clientes sem visão clara de contas a pagar/receber, fluxo de caixa e inadimplência.

**Solução QFiscal**:
- **Contas a Receber**: Controle completo de títulos, vencimentos, baixas
- **Contas a Pagar**: Gestão de fornecedores e despesas
- **Fluxo de Caixa**: Caixa diário com sangrias e suprimentos
- **Calendário Financeiro**: Visualização mensal de compromissos
- **Boletos via Mercado Pago**: Emissão automática
- **Baixa em Lote**: Agilidade para antecipações

### **4. Estoque Descontrolado**

**Dor**: Empresas comerciais sem controle de estoque, gerando divergências fiscais e prejuízos.

**Solução QFiscal**:
- **Movimentações de Estoque**: Entradas, saídas, ajustes
- **Kardex por Produto**: Histórico completo de movimentações
- **Integração Automática**: Baixa de estoque em vendas e PDV
- **Bloqueios Configuráveis**: Impedir vendas sem estoque
- **Estoque Negativo Opcional**: Flexibilidade para serviços

### **5. Gestão de Serviços Ineficiente**

**Dor**: Assistências técnicas e prestadores de serviço sem controle de ordens de serviço.

**Solução QFiscal**:
- **Ordens de Serviço Completas**: Desde recebimento até finalização
- **Status Personalizados**: Aguardando, Orçada, Em Execução, Finalizada
- **Aprovação de Orçamento**: Via sistema ou email com token
- **Gestão de Garantia**: Controle automático de prazos
- **Anexos**: Upload de fotos, laudos, documentos
- **Emissão de NFS-e**: Integrada ao fechamento

### **6. Dados Duplicados e Inconsistentes**

**Dor**: Informações desencontradas entre empresa e contabilidade.

**Solução QFiscal**:
- **Fonte Única da Verdade**: Contabilidade acessa os mesmos dados do cliente
- **Acesso Multi-usuário**: Diferentes níveis de permissão
- **API de Integração**: Exportação de dados para sistemas contábeis
- **Auditoria**: Logs de alterações importantes

### **7. Falta de Mobilidade**

**Dor**: Empreendedores presos ao escritório para acessar informações.

**Solução QFiscal**:
- **100% Web/Cloud**: Acesso de qualquer dispositivo
- **Responsivo**: Interface adaptada para mobile
- **PDV Integrado**: Vendas direto do navegador
- **Sem Instalação**: Zero manutenção de infraestrutura

---

## 🚀 Principais Funcionalidades

### **1. Gestão Fiscal e Tributária**

#### **Emissão de Documentos Fiscais**
- **NF-e (Nota Fiscal Eletrônica)**
  - Modelo 55 - Produtos
  - Validações automáticas de campos obrigatórios
  - Integração com Emissor Delphi (ACBr)
  - Ambiente homologação e produção
  - Download de XML e DANFE em PDF

- **NFC-e (Nota Fiscal ao Consumidor Eletrônica)**
  - Modelo 65 - Varejo/PDV
  - Emissão rápida para consumidor final
  - Impressão DANFE simplificado 80mm

- **NFS-e (Nota Fiscal de Serviços Eletrônica)**
  - Integração em desenvolvimento
  - RPS (Recibo Provisório de Serviços)
  - Compatível com prefeituras municipais

#### **Certificados Digitais**
- Upload de certificado A1 (PFX)
- Detecção automática de certificados Windows
- Gestão de senha criptografada
- Validação de vigência

#### **Configurações Fiscais**
- NCM (Nomenclatura Comum do Mercosul)
- CFOP (Código Fiscal de Operações)
- CST/CSOSN (Código de Situação Tributária)
- Alíquotas de ICMS, IPI, PIS, COFINS
- Regras tributárias por produto
- Configuração por tenant

### **2. Gestão Comercial**

#### **Orçamentos (Quotes)**
- Criação de propostas comerciais
- Validade configurável
- Conversão automática em pedido
- Impressão profissional
- Múltiplas formas de pagamento

#### **Pedidos (Orders)**
- Gestão completa do ciclo de venda
- Status: Aberto, Atendido, Cancelado
- Itens com descontos e acréscimos
- Frete configurável (modalidades SEFAZ)
- Volumes, peso bruto/líquido
- Transportadoras cadastradas
- Parcelamento (à vista, entrada + parcelas, boletos)
- Emissão de NF-e diretamente do pedido

#### **PDV / Ponto de Venda**
- Interface moderna e ágil
- Busca rápida de produtos
- Múltiplas formas de pagamento
- Cliente obrigatório ou opcional (Consumidor Final)
- Baixa automática de estoque
- Geração automática de recebíveis
- Emissão de NFC-e
- Impressão de comprovante (A4 ou 80mm)
- Fechamento de caixa

#### **Devoluções**
- Devolução total ou parcial
- Reabastecimento automático de estoque
- 3 modalidades financeiras:
  - Abater do contas a receber
  - Estornar (sai do caixa)
  - Gerar crédito para compras futuras
- NF-e de devolução (em desenvolvimento)

### **3. Gestão de Serviços**

#### **Ordens de Serviço**
- **Assistência Técnica Completa**
  - Dados do equipamento (marca, modelo, série)
  - Defeito relatado pelo cliente
  - Diagnóstico técnico
  - Orçamento com aprovação
  
- **Fluxo Completo de Status**
  - Aguardando (recém-aberta)
  - Orçada (com valor definido)
  - Em Execução (aprovada)
  - Finalizada (concluída)
  - Garantia (retrabalho sem custo)
  - Sem Reparo (cliente desistiu)
  - Cancelada

- **Aprovação de Orçamento**
  - Aprovação via sistema (usuário logado)
  - Aprovação via email com token único
  - Rejeição com motivo
  - Notificações automáticas

- **Garantia Inteligente**
  - Dias de garantia configuráveis
  - Cálculo automático da data limite
  - OS de garantia (valor zerado)
  - Controle de reincidências

- **Gestão de Itens**
  - Produtos e serviços
  - Descontos e acréscimos por item
  - Cálculo automático de totais

- **Anexos**
  - Upload de fotos, laudos, termos
  - Armazenamento seguro
  - Download direto

- **Emissão Fiscal**
  - NF-e para produtos vendidos
  - NFS-e para serviços prestados
  - Integração automática ao finalizar

### **4. Gestão Financeira**

#### **Contas a Receber**
- Cadastro manual ou automático (via vendas/OS)
- Vencimento, valor, cliente
- Status: Aberto, Parcial, Pago, Cancelado
- Baixa individual ou em lote
- Métodos: Dinheiro, PIX, Cartão, Boleto, Transferência
- Data de recebimento
- Taxas de antecipação (lançamento automático)
- Emissão de boletos via Mercado Pago

#### **Contas a Pagar**
- Cadastro de fornecedores e despesas
- Vencimento, valor, categoria
- Status: Aberto, Parcial, Pago, Cancelado
- Baixa individual
- Métodos de pagamento
- Comprovantes anexáveis

#### **Caixa do Dia**
- Abertura e fechamento de caixa
- Sangrias (retiradas)
- Suprimentos (reforços)
- Recebimentos do dia
- Pagamentos do dia
- Saldo final
- Conciliação automática

#### **Calendário Financeiro**
- Visualização mensal de vencimentos
- Contas a receber e a pagar
- Eventos personalizados
- Filtros por status
- Exportação de dados

### **5. Gestão de Estoque**

#### **Produtos**
- Cadastro completo (nome, SKU, EAN, NCM)
- Categorias
- Tipos: Produto físico ou Serviço
- Preço de venda
- Unidade de medida
- Dados fiscais (CFOP, CST, alíquotas)
- Status ativo/inativo

#### **Movimentações**
- **Entrada**: Compras, devoluções de clientes
- **Saída**: Vendas, perdas, uso interno
- **Ajuste**: Inventário, correções
- Documento de referência
- Observações
- Preço unitário (para custo médio)

#### **Kardex**
- Ficha de movimentação por produto
- Saldo acumulado
- Filtros por período e tipo
- Entrada, saída, saldo
- Histórico completo

#### **Integrações**
- Baixa automática em vendas (Pedidos)
- Baixa automática no PDV
- Reabastecimento em devoluções
- Bloqueios configuráveis (impedir venda sem estoque)
- Estoque negativo opcional

### **6. Cadastros e CRM**

#### **Clientes**
- Pessoa Física (CPF) ou Jurídica (CNPJ)
- Razão Social / Nome Fantasia
- Endereço completo (com busca de CEP)
- Email, telefone
- Status ativo/inativo
- Consumidor Final (para PDV)
- Limite de crédito (futuro)
- Histórico de compras e serviços

#### **Fornecedores**
- CNPJ, Razão Social
- Contato, email, telefone
- Produtos/serviços fornecidos
- Histórico de compras

#### **Transportadoras**
- CNPJ, Nome, Placa do veículo
- UF da placa
- RNTRC (Registro Nacional)
- Integração com frete em NF-e

### **7. Relatórios e Dashboards**

#### **Dashboard Personalizado**
- Widgets configuráveis por usuário
- Métricas principais:
  - Total de clientes
  - Recebimentos do dia/mês
  - Contas vencidas
  - Ordens de serviço abertas
  - Estoque baixo (em desenvolvimento)
  
- **Gráficos**
  - Faturamento mensal
  - Recebimentos vs Pagamentos
  - Vendas por categoria
  - OS por status

#### **Relatórios Gerenciais**
- Contas a receber (aberto, pago, vencido)
- Contas a pagar
- Fluxo de caixa
- Ordens de serviço
- Vendas por período
- Produtos mais vendidos
- Clientes com maior faturamento
- Exportação Excel/PDF

### **8. Multi-tenant e Isolamento**

#### **Arquitetura Robusta**
- Cada empresa (tenant) tem seus próprios dados
- Isolamento total via `tenant_id`
- Sem possibilidade de vazamento entre empresas
- Usuários vinculados a apenas um tenant

#### **Gestão de Usuários**
- Administrador do tenant
- Usuários com permissões granulares
- Roles: Admin, Técnico, Operador, Vendedor
- Permissões customizáveis:
  - Visualizar, Criar, Editar, Excluir
  - Módulos: Clientes, Produtos, OS, Vendas, Financeiro, NFe, etc.

### **9. Sistema de Planos e Assinaturas**

#### **Planos Disponíveis**

| Plano | Preço/mês | Usuários | Clientes | Produtos | NF-e | PDV | Emissor | Suporte |
|-------|-----------|----------|----------|----------|------|-----|---------|---------|
| **Gratuito** | R$ 0,00 | 1 | 50 | 50 | ❌ | ❌ | ❌ | Email |
| **Emissor Fiscal** | R$ 39,90 | 1 | 50 | 50 | ❌ | ❌ | ✅ | Email |
| **Básico** | R$ 49,90 | 3 | 200 | ∞ | ✅ | ✅ | ❌ | Email |
| **Profissional** | R$ 99,90 | 10 | 1.000 | ∞ | ✅ | ✅ | ✅ | Prioritário |
| **Enterprise** | R$ 199,90 | ∞ | ∞ | ∞ | ✅ | ✅ | ✅ | 24/7 |


#### **Sistema de Cobrança**
- **Checkout Mercado Pago**: Cartão, PIX, boleto
- **Renovação Automática**: Geração mensal de fatura
- **Modo Limitado**: Após vencimento, volta ao plano gratuito (sem bloqueio total)
- **Upgrade/Downgrade**: Alteração de plano a qualquer momento
- **Histórico de Pagamentos**: Tela de faturas pagas

---

## 🏗️ Benefícios Específicos para Contabilidades

### **1. Geração de Receita Recorrente**

**Como Funciona:**
- Contabilidade opera como **white label partner**
- Cada cliente contratado gera **comissão recorrente**
- Plataforma cuida da cobrança e renovação
- Repasse manual (Fase 1) ou split automático (Fase 2)

**Exemplo Prático:**
```
Cliente contrata Plano Profissional: R$ 99,90/mês
Comissão do parceiro (30%): R$ 29,97/mês
10 clientes ativos = R$ 299,70/mês recorrente
50 clientes ativos = R$ 1.498,50/mês recorrente
```

### **2. Diferenciação no Mercado**

- **Agregação de Valor**: Não é apenas contabilidade, é consultoria + tecnologia
- **Fidelização**: Clientes dependem da plataforma para operar
- **Modernização**: Imagem de escritório inovador e digital
- **Autoridade**: "Temos nossa própria plataforma de gestão"

### **3. Redução de Retrabalho**

#### **Antes do QFiscal:**
- Clientes enviam planilhas bagunçadas
- Dados inconsistentes entre NF-e e contabilidade
- Telefone/WhatsApp constante para conferências
- Erros fiscais frequentes

#### **Com o QFiscal:**
- Dados padronizados e validados
- Acesso direto às mesmas informações do cliente
- Emissão fiscal correta desde a origem
- Exportação automática para sistema contábil

### **4. Escalabilidade**

- **Onboarding Rápido**: Cadastro em 2 etapas, cliente já pode usar
- **Suporte Técnico**: Plataforma cuida do suporte básico
- **Infraestrutura**: Sem preocupação com servidores, backups, atualizações
- **Crescimento Exponencial**: Atender 10 ou 1000 clientes com o mesmo esforço

### **5. Compliance e Segurança**

- **Dados na Nuvem**: Backups automáticos, recuperação de desastres
- **Isolamento Multi-tenant**: Dados de cada empresa totalmente separados
- **Logs de Auditoria**: Rastreabilidade de ações importantes
- **Certificados Criptografados**: Gestão segura de certificados digitais
- **LGPD Ready**: Arquitetura preparada para conformidade

### **6. Visibilidade e Controle**

#### **Painel do Parceiro (Contabilidade)**
- Listar todos os tenants/clientes
- Visualizar status de assinaturas
- Acessar dados financeiros consolidados
- Relatórios de comissões
- Gerenciar usuários por cliente

#### **Acesso aos Dados**
- Contabilidade pode ter usuário admin em cada tenant
- Exportação de dados para sistemas contábeis
- API para integrações customizadas

---

## 🎨 White Label: Como Funciona para Contabilidades

### **Estrutura de Subdomínios**

Cada escritório de contabilidade opera em seu próprio ambiente:

```
Contabilidade A: contabila.qfiscal.com.br
  ├── Cliente 1 (Tenant): Padaria do João
  ├── Cliente 2 (Tenant): Loja da Maria
  └── Cliente 3 (Tenant): Oficina do Pedro

Contabilidade B: contabilb.qfiscal.com.br
  ├── Cliente 1 (Tenant): Salão da Ana
  ├── Cliente 2 (Tenant): Consultoria XYZ
  └── Cliente 3 (Tenant): E-commerce ABC
```

### **Personalização de Marca**

Cada parceiro configura:

- **Logo**: Upload do logotipo do escritório
- **Cores Primária e Secundária**: Identidade visual customizada
- **Tema**: Claro ou escuro
- **Domínio Próprio** (opcional): `sistema.suacontabilidade.com.br`

### **Comissionamento**

#### **Fase 1: Repasse Manual (Atual)**
1. Sistema registra `partner_id` em todos os tenants, invoices e payments
2. Relatórios mostram faturamento por parceiro
3. Cálculo automático de comissão (ex: 30% do valor)
4. Repasse manual via PIX/transferência

#### **Fase 2: Split Automático (Futuro)**
1. Parceiro conecta conta Mercado Pago via OAuth
2. Checkout usa o token do parceiro (collector)
3. Plataforma define `application_fee` (nossa comissão)
4. Split acontece automaticamente no momento do pagamento
5. Parceiro recebe direto na conta

### **Gestão de Clientes (Tenants)**

Cada tenant criado sob o subdomínio do parceiro:
- Fica vinculado automaticamente (`tenant.partner_id`)
- Herda branding do parceiro (logo, cores)
- Gera comissionamento nas renovações
- É gerenciado pelo painel do parceiro

---


## 📊 Módulos Detalhados

### **Módulo: Ordens de Serviço**

#### **Funcionalidades**
- Dashboard de OS (Hoje: finalizadas, valor, em aberto)
- Criação com dados do equipamento
- Orçamento com aprovação
- Anexos (fotos, laudos)
- Técnicos responsáveis
- Gestão de garantia
- Impressão de OS
- Emissão de NFS-e

#### **Benefício para a Contabilidade**
- Receita de serviço documentada
- Controle de reincidências (garantia)
- Histórico de atendimentos
- Base para apuração de impostos

### **Módulo: PDV (Ponto de Venda)**

#### **Funcionalidades**
- Interface de venda rápida
- Busca de produtos
- Cliente obrigatório ou Consumidor Final
- Múltiplas formas de pagamento
- Parcelamento
- Baixa automática de estoque
- Geração de recebíveis
- Emissão de NFC-e
- Impressão de comprovante

#### **Benefício para a Contabilidade**
- Vendas no varejo organizadas
- Emissão fiscal automática
- Dados prontos para contabilização
- Conciliação de caixa facilitada

### **Módulo: Gestão Fiscal**

#### **Funcionalidades**
- Cadastro de emissores (CNPJ, IE, IM)
- Upload de certificado digital
- Configuração de séries de NF-e
- Ambiente homologação/produção
- Validações pré-emissão
- Armazenamento de XMLs
- Download de DANFEs
- Consultas de status SEFAZ

#### **Benefício para a Contabilidade**
- Emissões centralizadas
- XMLs organizados por cliente
- Validações impedem erros
- Conformidade fiscal garantida

### **Módulo: Financeiro**

#### **Funcionalidades**
- Contas a receber/pagar
- Calendário de vencimentos
- Baixa individual e em lote
- Emissão de boletos
- Taxas de antecipação
- Caixa do dia
- Conciliação bancária (em desenvolvimento)
- Relatórios gerenciais

#### **Benefício para a Contabilidade**
- Fluxo de caixa real
- Inadimplência visível
- Receitas e despesas categorizadas
- DRE simplificada pronta

---

## 🔐 Segurança e Compliance

### **Proteção de Dados**
- Senhas com hash bcrypt
- Certificados PFX criptografados
- Tokens API seguros
- HTTPS obrigatório

### **Isolamento Multi-tenant**
- Filtros por `tenant_id` em todas as queries
- Middleware de verificação de tenant
- Impossibilidade de acessar dados de outros tenants
- Logs de auditoria

### **Backup e Recuperação**
- Backups diários automáticos
- Retenção de 30 dias
- Recuperação point-in-time
- Testes periódicos de restore

### **LGPD (Lei Geral de Proteção de Dados)**
- Dados pessoais criptografados
- Logs de acesso
- Consentimento de uso (em desenvolvimento)
- Portabilidade de dados (exportação)
- Direito ao esquecimento (exclusão de conta)

---

## 🚀 Roadmap e Evolução

### **Já Implementado (v1.0)**
- ✅ Sistema multi-tenant completo
- ✅ Gestão de clientes, produtos, fornecedores
- ✅ Ordens de serviço com aprovação
- ✅ Orçamentos e Pedidos
- ✅ PDV completo
- ✅ Estoque com kardex
- ✅ Contas a receber/pagar
- ✅ Calendário financeiro
- ✅ Emissão de NF-e (via Delphi)
- ✅ Emissão de NFC-e
- ✅ Sistema de planos
- ✅ Checkout Mercado Pago
- ✅ Devoluções
- ✅ Relatórios gerenciais
- ✅ Dashboard customizável
- ✅ White Label base (subdomínios)

### **Em Desenvolvimento (v1.5)**
- 🔄 NFS-e (Nota Fiscal de Serviços)
- 🔄 Conciliação bancária via OFX
- 🔄 Relatórios avançados (DRE, DFC)
- 🔄 Crédito de ICMS/PIS/COFINS
- 🔄 Dashboard do parceiro white label
- 🔄 API REST completa

### **Planejado (v2.0)**
- 📋 Split automático Mercado Pago (Marketplace)
- 📋 Integração WooCommerce multi-tenant
- 📋 Boletos outros gateways (Asaas, PagSeguro)
- 📋 Contratos de manutenção recorrente
- 📋 Chat interno (tenant ↔ contabilidade)
- 📋 Importação de XMLs de compra (CT-e, NF-e)
- 📋 EFD ICMS/IPI (SPED Fiscal)
- 📋 EFD Contribuições (PIS/COFINS)
- 📋 eSocial (folha simplificada)
- 📋 Integrações contábeis (Domínio Sistemas, Questor, etc.)

### **Futuro (v3.0+)**
- 🔮 App Mobile (iOS/Android)
- 🔮 BI e Analytics avançado
- 🔮 Integrações bancárias (Open Finance)
- 🔮 Machine Learning para previsões
- 🔮 Automações inteligentes (RPA)

---

## 💡 Casos de Uso

### **Caso 1: Escritório Pequeno (20 clientes)**

**Perfil**: Contabilidade familiar, 2 sócios, atende MEIs e pequenas empresas.

**Implementação**:
- White label: `contabilsimples.qfiscal.com.br`
- Oferecer Plano Gratuito para MEIs (captação)
- Plano Básico (R$ 49,90) para pequenos comércios
- Comissão de 30% = R$ 14,97/cliente

**Resultado**:
- 15 clientes no Plano Básico = R$ 224,55/mês recorrente
- Redução de 40% no retrabalho
- Clientes mais organizados
- Diferencial competitivo

### **Caso 2: Escritório Médio (100 clientes)**

**Perfil**: Contabilidade estruturada, 5 contadores, atende PMEs.

**Implementação**:
- White label: `grupoconta.qfiscal.com.br`
- Plano Básico para varejo
- Plano Profissional para indústria/serviços
- Comissão média de 35%

**Resultado**:
- 60 clientes Básico (R$ 49,90) + 40 Profissional (R$ 99,90)
- Receita recorrente: R$ 2.445,00/mês
- Base de clientes fidelizada
- Redução de 60% em solicitações de dados

### **Caso 3: Contabilidade Digital (500+ clientes)**

**Perfil**: Operação 100% online, escalável, atende Brasil todo.

**Implementação**:
- White label com domínio próprio: `sistema.contabdigital.com.br`
- Onboarding automatizado
- Todos os planos disponíveis
- API de integração com sistema contábil próprio

**Resultado**:
- 300 Básico + 150 Profissional + 50 Enterprise
- Receita recorrente: R$ 35.000+/mês
- Operação escalável
- Custo marginal baixo por cliente adicional

---

## 📞 Modelo de Parceria

### **Como se Tornar um Parceiro White Label**

#### **Passo 1: Cadastro**
- Contato com a equipe QFiscal
- Preenchimento de dados do escritório
- Escolha do subdomínio (ex: `suacontabil.qfiscal.com.br`)

#### **Passo 2: Configuração**
- Upload de logo
- Definição de cores da marca
- Personalização de tema
- Configuração de comissão (padrão: 30%)

#### **Passo 3: Onboarding**
- Treinamento da equipe (2h online)
- Documentação completa
- Ambiente de testes
- Suporte técnico dedicado

#### **Passo 4: Lançamento**
- Migração dos primeiros clientes
- Campanha de divulgação
- Material de marketing fornecido
- Acompanhamento no primeiro mês

### **Investimento**

- **Taxa de Setup**: R$ 0 (lançamento promocional)
- **Mensalidade do Parceiro**: R$ 0
- **Comissão**: 30% a do valor pago pelo cliente final
- **Suporte Técnico**: Incluído
- **Atualizações**: Incluídas

### **Suporte ao Parceiro**

- **Onboarding**: Treinamento completo
- **Documentação**: Manuais e vídeos
- **Suporte Técnico**: Email e WhatsApp (horário comercial)
- **Atualizações**: Novas funcionalidades sem custo adicional
- **Materiais de Marketing**: Templates, apresentações, propostas

---

## 🎯 Diferenciais Competitivos

### **1. Arquitetura Multi-tenant Nativa**
Não é adaptação de sistema single-tenant. Foi projetado desde o início para operar múltiplas empresas isoladas.

### **2. White Label Completo**
Não é apenas logo e cores. É subdomínio próprio, branding total, comissionamento e gestão independente.

### **3. Emissão Fiscal Brasileira**
Integração nativa  (padrão do mercado). Não depende de serviços terceiros caros.

### **4. Plano Emissor Fiscal Dedicado**
Único no mercado: cliente que só precisa de emissor fiscal paga R$ 39,90 e tem emissor completo.

### **5. PDV Integrado**
Não é módulo separado. PDV totalmente integrado ao ERP, estoque e fiscal.

### **6. Ordens de Serviço Completas**
Assistência técnica de verdade, com aprovação de orçamento, garantia, anexos.

### **7. Checkout Brasileiro**
Mercado Pago nativo. PIX, boleto, cartão. Split automático (em breve).

### **8. Código Moderno e Sustentável**
Fácil de manter e evoluir.

---

## 📈 Métricas e KPIs para Contabilidades

### **Métricas de Adoção**
- Taxa de ativação (clientes que começam a usar)
- Taxa de uso ativo (login nos últimos 7 dias)
- Funcionalidades mais usadas
- Notas fiscais emitidas/mês

### **Métricas Financeiras**
- MRR (Monthly Recurring Revenue) por parceiro
- Churn rate (cancelamentos)
- LTV (Lifetime Value) por cliente
- CAC (Custo de Aquisição) - facilitado pelo white label

### **Métricas de Qualidade**
- Redução de retrabalho (% de horas economizadas)
- Erros fiscais evitados
- Tempo médio de fechamento contábil
- Satisfação do cliente (NPS)

---

## 🏆 Conclusão: Por Que Escolher o QFiscal?

### **Para Contabilidades que Querem:**

✅ **Gerar Receita Recorrente** com tecnologia própria  
✅ **Diferenciar-se** no mercado competitivo  
✅ **Fidelizar Clientes** com valor agregado real  
✅ **Reduzir Retrabalho** com dados organizados desde a origem  
✅ **Escalar** sem aumentar custos proporcionalmente  
✅ **Ter sua Própria Marca** em uma plataforma robusta  
✅ **Oferecer Compliance Fiscal** com emissão integrada  
✅ **Modernizar** sua operação e imagem  

### **Tecnologia de Ponta + Modelo de Negócio Inovador**

O QFiscal não é apenas um software. É uma **plataforma de negócio** que transforma contabilidades em **provedoras de tecnologia**, gerando receita recorrente e fidelizando clientes através de valor real.

---

## 📞 Contato e Próximos Passos

### **Quer conhecer mais?**

- **Email**: contato@qfiscal.com.br
- **WhatsApp**: (11) 94714-6126
- **Site**: www.qfiscal.com.br
- **Demonstração**: Agende uma apresentação personalizada

### **Processo de Contratação**

1. **Reunião Inicial** (30min): Entendimento do seu escritório
2. **Demonstração** (1h): Apresentação completa da plataforma
3. **Proposta Comercial**: Modelo de comissionamento personalizado
4. **Setup e Treinamento** (1 semana): Ambiente pronto para usar
5. **Lançamento**: Primeiros clientes migrando

---

**Documento gerado em**: Outubro de 2025  
**Versão**: 1.0  

---

*QFiscal ERP - Transformando Contabilidades em Provedoras de Tecnologia*

