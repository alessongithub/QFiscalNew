# Fluxo Completo de Emissão de NFe - QFiscal ERP

## Visão Geral

Este documento descreve o fluxo completo para emissão de Nota Fiscal Eletrônica (NFe) no sistema QFiscal, incluindo todos os pré-requisitos, validações e passos necessários para que empresas (tenants) possam emitir NFe com sucesso.

## 1. Pré-requisitos do Sistema

### 1.1 Configuração do Emissor Delphi
- **Emissor Delphi** deve estar rodando na porta `18080`
- **Certificado digital** configurado e válido
- **Ambiente** configurado (homologação/produção)
- **Conexão** com SEFAZ estabelecida

### 1.2 Permissões do Usuário
- `orders.edit` - Para editar pedidos
- `nfe.emit` - Para emitir NFe
- `orders.freight.assign` - Para definir frete (se necessário)

## 2. Configuração de Produtos (Obrigatório)

### 2.1 Campos Fiscais Essenciais
Cada produto deve ter preenchido:

```php
// Campos obrigatórios para NFe
'ncm' => 'string',           // Código NCM (8 dígitos)
'cfop' => 'string',          // CFOP (4 dígitos)
'origin' => 'integer',       // Origem da mercadoria (0-8)
'cst_icms' => 'string',     // CST ICMS OU
'csosn' => 'string',         // CSOSN (para Simples Nacional)
'aliquota_icms' => 'decimal', // Alíquota ICMS
'cest' => 'string',          // CEST (opcional)
'ean' => 'string',           // Código de barras (opcional)
'unit' => 'string',          // Unidade comercial
```

### 2.2 Validações Automáticas
- ✅ **NCM**: Obrigatório, 8 dígitos
- ✅ **CFOP**: Obrigatório, 4 dígitos  
- ✅ **CST/CSOSN**: Pelo menos um deve estar preenchido
- ✅ **Origem**: Obrigatória (0-8)

## 3. Fluxo de Criação de Pedidos

### 3.1 Pedidos Normais (Manual)

#### Passo 1: Criar Pedido
1. Acessar **Pedidos** → **Novo Pedido**
2. Selecionar **Cliente**
3. Adicionar **Produtos** (com campos fiscais preenchidos)
4. Definir **Quantidades** e **Preços**
5. Salvar pedido (status: `open`)

#### Passo 2: Finalizar Pedido
1. Clicar em **"Finalizar Pedido"**
2. Configurar **Frete** (se produtos físicos):
   - Modalidade: `0` (Por conta do emitente), `1` (Por conta do destinatário), `2` (Por conta de terceiros), `9` (Sem frete)
   - Transportadora (se modalidade 0 ou 2)
   - Valor do frete
   - **Volumes**: Quantidade, espécie, peso bruto/líquido
   - **Despesas**: Seguro, outras despesas
3. Definir **Forma de Pagamento**:
   - **À vista**: Método único
   - **Parcelado**: Entrada + parcelas
   - **Misto**: Entrada + parcelas
4. Adicionar **Observações Fiscais** (opcional):
   - `additional_info` (infCpl)
   - `fiscal_info` (infAdFisco)
5. Confirmar finalização (status: `fulfilled`)

### 3.2 Pedidos PDV (Ponto de Venda)

#### Passo 1: Venda no PDV
1. Acessar **PDV** → **Nova Venda**
2. Selecionar **Cliente** (ou usar "Consumidor Final" automático)
3. Adicionar **Produtos** via busca
4. Definir **Forma de Pagamento**:
   - À vista, parcelado ou misto
   - Métodos: Dinheiro, cartão, PIX, boleto, etc.
5. Finalizar venda

#### Passo 2: Processamento Automático
- ✅ **Status**: Automaticamente `fulfilled`
- ✅ **Cliente**: "Consumidor Final" se não selecionado
- ✅ **Recebíveis**: Criados automaticamente conforme pagamento
- ✅ **Estoque**: Baixado automaticamente

## 4. Validações Pré-Emissão

### 4.1 Validações Automáticas do Sistema

#### Produtos
```php
// Validação por produto
if (empty($produto->ncm)) {
    return 'Produto {nome} sem NCM';
}
if (empty($produto->cfop)) {
    return 'Produto {nome} sem CFOP';
}
if (empty($produto->cst_icms) && empty($produto->csosn)) {
    return 'Produto {nome} sem CST/CSOSN';
}
```

#### Frete (Produtos Físicos)
```php
// Se modalidade não é "Sem frete" (9)
if ($freight_mode !== 9) {
    // Modalidade 0 ou 2 exige transportadora
    if (in_array($freight_mode, [0, 2])) {
        if (empty($carrier_id)) return 'Frete exige transportadora';
        if ($freight_cost === null) return 'Informe o valor do frete';
    }
    // Volumes e peso obrigatórios
    if ($volume_qtd <= 0 || $peso_bruto <= 0) {
        return 'Informe volumes e peso para o frete';
    }
}
```

#### Pagamentos
```php
// Soma dos recebíveis deve bater com total do pedido
$sum_receivables = Receivable::where('order_id', $order->id)->sum('amount');
$total_order = $order->total_amount;
if (abs($sum_receivables - $total_order) > 0.01) {
    return 'Soma dos pagamentos difere do total do pedido';
}
```

#### Status do Pedido
```php
// Pedido deve estar finalizado
if ($order->status !== 'fulfilled') {
    return 'Para emitir NF-e, o pedido precisa estar Finalizado';
}
```

### 4.2 Validações de Negócio

#### Natureza da Operação
- **Obrigatória**: `natOp` (ex: "Venda de mercadoria")
- **Finalidade**: `finNFe` (1=Normal, 2=Complementar, 3=Ajuste)

#### Cliente
- **Consumidor Final**: CPF/CNPJ automático se não informado
- **Dados completos**: Nome, documento, endereço

## 5. Processo de Emissão

### 5.1 Acesso à Emissão

#### Para Pedidos Normais
1. **Pedidos** → **Editar Pedido**
2. Verificar se botão **"Emitir NFe"** está habilitado
3. Clicar em **"Emitir NFe"**

#### Para Pedidos PDV
1. **PDV** → **Vendas**
2. Localizar venda desejada
3. Clicar em **"Emitir NFe"**

### 5.2 Configurações da NFe

#### Parâmetros Padrão (PDV)
```php
'type' => 'products',
'operation_type' => 'venda',
'tpNF' => 1,                    // 1=Saída
'finNFe' => 1,                  // 1=Normal
'cfop' => '5102',               // Venda
'natOp' => 'Venda de mercadoria (PDV)'
```

#### Parâmetros Personalizáveis (Pedidos Normais)
- **Tipo**: `products` (produtos), `services` (serviços), `mixed` (misto)
- **Operação**: `venda`, `devolucao`, etc.
- **CFOP**: Personalizável (padrão: 5102)
- **Natureza**: Personalizável
- **Finalidade**: 1=Normal, 2=Complementar, 3=Ajuste

### 5.3 Montagem do Payload

#### Estrutura do JSON Enviado
```json
{
  "tipo": "nfe",
  "numero_pedido": "000001",
  "cliente": {
    "nome": "Cliente Exemplo",
    "cpf_cnpj": "12345678901",
    "endereco": "...",
    "municipio": "...",
    "uf": "RS"
  },
  "produtos": [
    {
      "id": 1,
      "nome": "Produto Exemplo",
      "ncm": "12345678",
      "cfop": "5102",
      "quantidade": 1.000,
      "valor_unitario": 100.00,
      "valor_total": 100.00,
      "cst_icms": "00",
      "aliquota_icms": 18.00
    }
  ],
  "configuracoes": {
    "cfop": "5102",
    "natOp": "Venda de mercadoria",
    "tpNF": 1,
    "finNFe": 1,
    "ambiente": "homologacao",
    "serie": "1"
  },
  "transporte": {
    "modalidade": 0,
    "responsavel": "emitente",
    "transportadora_id": 1,
    "valor_frete": 10.00,
    "volumes": {
      "quantidade": 1,
      "especie": "Caixa",
      "peso_bruto": 1.500,
      "peso_liquido": 1.200
    },
    "despesas_acessorias": {
      "valor_seguro": 5.00,
      "outras_despesas": 2.00
    }
  },
  "pagamentos": [
    {
      "forma": "Dinheiro",
      "valor": 100.00,
      "tPag": "01",
      "tpag_hint": "Dinheiro em espécie"
    }
  ],
  "observacoes": {
    "inf_complementar": "Observações complementares",
    "inf_fisco": "Informações ao fisco"
  }
}
```

### 5.4 Mapeamento de Pagamentos

#### Códigos tPag Oficiais
```php
'DINHEIRO' => '01',        // Dinheiro
'CHEQUE' => '02',          // Cheque
'CARTAO_CREDITO' => '03',  // Cartão de crédito
'CARTAO_DEBITO' => '04',   // Cartão de débito
'CREDITO_LOJA' => '05',    // Crédito da loja
'BOLETO' => '15',          // Boleto bancário
'DEPOSITO' => '16',        // Depósito bancário
'PIX' => '17',             // PIX
'OUTROS' => '99'           // Outros
```

#### Override e Hints
- **tpag_override**: Força código específico
- **tpag_hint**: Descrição adicional (ex: "TEF Crédito Banrisul")

## 6. Processamento e Resposta

### 6.1 Envio para Delphi
1. **Validação** de disponibilidade do emissor
2. **Envio** do payload via HTTP POST
3. **Timeout**: 30 segundos
4. **Log** completo da transação

### 6.2 Respostas Possíveis

#### Sucesso
```json
{
  "success": true,
  "data": {
    "numero": "123456",
    "chave": "12345678901234567890123456789012345678901234",
    "protocolo": "123456789012345",
    "status": "autorizada"
  }
}
```

#### Erro
```json
{
  "success": false,
  "error": "Descrição do erro",
  "code": "CODIGO_ERRO"
}
```

### 6.3 Atualização do Pedido
- **nfe_issued_at**: Timestamp da emissão
- **nfe_number**: Número da NFe
- **nfe_key**: Chave de acesso
- **Status**: Bloqueio para alterações

## 7. Troubleshooting

### 7.1 Erros Comuns

#### "Emissor indisponível"
- ✅ Verificar se Delphi está rodando na porta 18080
- ✅ Testar endpoint `/api/status`

#### "Certificado inválido"
- ✅ Verificar certificado no Delphi
- ✅ Validar data de validade
- ✅ Confirmar configuração A1/A3

#### "Produto sem NCM/CFOP/CST"
- ✅ Completar campos fiscais do produto
- ✅ Validar formato dos códigos
- ✅ Verificar origem da mercadoria

#### "Soma dos pagamentos difere"
- ✅ Verificar recebíveis do pedido
- ✅ Confirmar valores e parcelas
- ✅ Recalcular totais

### 7.2 Logs e Monitoramento

#### Logs Importantes
- **Orders.issueNfe**: Chamada do método
- **Orders.issueNfe payload**: Dados enviados
- **Orders.issueNfe result**: Resposta recebida
- **NFeService**: Comunicação com Delphi

#### Localização dos Logs
- **Laravel**: `storage/logs/laravel.log`
- **Delphi**: Logs do aplicativo emissor

## 8. Fluxograma Resumido

```
┌─────────────────┐
│   Criar Pedido  │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Adicionar      │
│  Produtos       │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Finalizar      │
│  Pedido         │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Configurar     │
│  Frete/Pagamento│
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Status:        │
│  Fulfilled     │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Emitir NFe     │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Validações     │
│  Automáticas    │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Envio para     │
│  Delphi/SEFAZ    │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  NFe Autorizada │
└─────────────────┘
```

## 9. Checklist para Tenants

### ✅ Antes de Emitir NFe

#### Produtos
- [ ] NCM preenchido (8 dígitos)
- [ ] CFOP preenchido (4 dígitos)
- [ ] CST ou CSOSN preenchido
- [ ] Origem da mercadoria definida
- [ ] Alíquota ICMS configurada

#### Pedido
- [ ] Status "Finalizado"
- [ ] Cliente com dados completos
- [ ] Recebíveis criados e valores corretos
- [ ] Frete configurado (se produtos físicos)
- [ ] Volumes e peso informados (se frete)

#### Sistema
- [ ] Emissor Delphi rodando
- [ ] Certificado válido
- [ ] Conexão com SEFAZ
- [ ] Permissões de usuário

### ✅ Após Emissão
- [ ] NFe autorizada pela SEFAZ
- [ ] Número e chave anotados
- [ ] XML baixado e arquivado
- [ ] Pedido bloqueado para alterações

---

**Documento atualizado em**: Janeiro 2025  
**Versão**: 1.0  
**Sistema**: QFiscal ERP
