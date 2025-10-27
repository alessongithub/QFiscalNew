# 🔗 **INTEGRAÇÃO ERP LARAVEL ↔ EMISSOR DELPHI (FIREBIRD)**

## 📋 **VISÃO GERAL**

Este documento descreve a estrutura do banco de dados Firebird do emissor Delphi e como integrar com o ERP Laravel para emissão automática de NFe.

---

## 🗄️ **ESTRUTURA DO BANCO FIREBIRD**

### **📊 TABELAS PRINCIPAIS**

#### **1. PESSOAS (Clientes/Fornecedores)**
```sql
CREATE TABLE PESSOAS (
    ID_PESSOA            INTEGER NOT NULL,           -- Chave primária
    FG_TIPOPESSOA        VARCHAR(25),                -- Tipo: Física/Jurídica
    NR_CNPJ_CPF          VARCHAR(15),                -- CNPJ/CPF
    NR_IE_RG             VARCHAR(20),                -- IE/RG
    DS_RAZAOSOCIAL_NOME  VARCHAR(255),               -- Nome/Razão Social
    DS_FANTASIA_APELIDO  VARCHAR(255),               -- Nome Fantasia/Apelido
    NR_CEP               VARCHAR(8),                 -- CEP
    DS_ENDERECO          VARCHAR(255),               -- Endereço
    NR_NUMERO            VARCHAR(25),                -- Número
    DS_COMPLEMENTO       VARCHAR(100),               -- Complemento
    DS_BAIRRO            VARCHAR(100),               -- Bairro
    CD_MUNICIPIO         INTEGER,                    -- Código do Município
    DS_MUNICIPIO         VARCHAR(100),               -- Nome do Município
    CD_UF                VARCHAR(2),                 -- UF
    NR_CELULAR           VARCHAR(20),                -- Celular
    NR_TELEFONE1         VARCHAR(20),                -- Telefone
    DS_EMAIL             VARCHAR(255),               -- Email
    FG_CONSUMIDOR_FINAL  VARCHAR(25),                -- Consumidor Final
    DS_OBS               VARCHAR(255)                -- Observações
);
```

#### **2. PRODUTOS**
```sql
CREATE TABLE PRODUTOS (
    ID_PRODUTO         INTEGER NOT NULL,             -- Chave primária
    CD_INTERNO         VARCHAR(45),                  -- Código interno
    CD_GTIN            VARCHAR(45),                  -- Código de barras
    DS_NOME            VARCHAR(255),                 -- Nome do produto
    CD_UNIDADE         VARCHAR(15),                  -- Unidade comercial
    CD_NCM             VARCHAR(20),                  -- NCM
    CD_CEST            VARCHAR(20),                  -- CEST
    CD_ORIGEM_PRODUTO  INTEGER,                      -- Origem do produto
    VR_COMPRA          NUMERIC(10,2),                -- Valor de compra
    VR_VENDA           NUMERIC(10,2),                -- Valor de venda
    DS_OBSERVACAO      VARCHAR(255)                  -- Observações
);
```

#### **3. TABELAS DE APOIO**
```sql
-- NCM (Classificação fiscal)
CREATE TABLE NCM (
    ID_NCM  INTEGER NOT NULL,
    CD_NCM  VARCHAR(15),                            -- Código NCM
    DS_NCM  VARCHAR(1000)                           -- Descrição NCM
);

-- CEST (Classificação de origem)
CREATE TABLE CEST (
    ID_CEST  INTEGER NOT NULL,
    CD_NCM   VARCHAR(15),                           -- NCM relacionado
    CD_CEST  VARCHAR(15),                           -- Código CEST
    DS_CEST  VARCHAR(1000)                          -- Descrição CEST
);

-- CST (Classificação tributária)
CREATE TABLE CST (
    ID_CST   INTEGER NOT NULL,
    FG_TIPO  VARCHAR(10),                           -- Tipo (ICMS, PIS, COFINS)
    CD_CST   VARCHAR(10),                           -- Código CST
    DS_CST   VARCHAR(255)                           -- Descrição CST
);

-- CFOP (Classificação de operações)
CREATE TABLE CFOP (
    CD_CFOP  INTEGER NOT NULL,                      -- Código CFOP
    DS_CFOP  VARCHAR(1000)                          -- Descrição CFOP
);

-- UNIDADES
CREATE TABLE UNIDADES (
    ID_UNIDADE  INTEGER NOT NULL,
    CD_UNIDADE  VARCHAR(15),                        -- Código da unidade
    DS_UNIDADE  VARCHAR(25)                         -- Descrição da unidade
);
```

---

## 🔄 **MAPEAMENTO LARAVEL ↔ FIREBIRD**

### **📦 PRODUTOS**
| Campo Laravel | Campo Firebird | Tipo | Obrigatório |
|---------------|----------------|------|-------------|
| `id` | `ID_PRODUTO` | INTEGER | ✅ |
| `name` | `DS_NOME` | VARCHAR(255) | ✅ |
| `sku` | `CD_INTERNO` | VARCHAR(45) | ✅ |
| `ean` | `CD_GTIN` | VARCHAR(45) | ⚠️ |
| `unit` | `CD_UNIDADE` | VARCHAR(15) | ✅ |
| `ncm` | `CD_NCM` | VARCHAR(20) | ✅ |
| `cest` | `CD_CEST` | VARCHAR(20) | ⚠️ |
| `origin` | `CD_ORIGEM_PRODUTO` | INTEGER | ✅ |
| `price` | `VR_VENDA` | NUMERIC(10,2) | ✅ |

### **👤 CLIENTES**
| Campo Laravel | Campo Firebird | Tipo | Obrigatório |
|---------------|----------------|------|-------------|
| `id` | `ID_PESSOA` | INTEGER | ✅ |
| `name` | `DS_RAZAOSOCIAL_NOME` | VARCHAR(255) | ✅ |
| `cpf_cnpj` | `NR_CNPJ_CPF` | VARCHAR(15) | ✅ |
| `ie_rg` | `NR_IE_RG` | VARCHAR(20) | ⚠️ |
| `type` | `FG_TIPOPESSOA` | VARCHAR(25) | ✅ |
| `address` | `DS_ENDERECO` | VARCHAR(255) | ✅ |
| `number` | `NR_NUMERO` | VARCHAR(25) | ✅ |
| `complement` | `DS_COMPLEMENTO` | VARCHAR(100) | ✅ |
| `neighborhood` | `DS_BAIRRO` | VARCHAR(100) | ✅ |
| `city` | `DS_MUNICIPIO` | VARCHAR(100) | ✅ |
| `state` | `CD_UF` | VARCHAR(2) | ✅ |
| `zip_code` | `NR_CEP` | VARCHAR(8) | ✅ |
| `phone` | `NR_TELEFONE1` | VARCHAR(20) | ✅ |
| `email` | `DS_EMAIL` | VARCHAR(255) | ✅ |
| `consumidor_final` | `FG_CONSUMIDOR_FINAL` | VARCHAR(25) | ✅ |

---

## 🚀 **IMPLEMENTAÇÃO DA INTEGRAÇÃO**

### **1. API LARAVEL → DELPHI**

#### **Endpoint: POST /api/emitir-nfe**
```json
{
  "tipo": "nfe",
  "cliente": {
    "id": 123,
    "nome": "João Silva",
    "cpf_cnpj": "123.456.789-00",
    "endereco": "Rua das Flores, 123",
    "cidade": "São Paulo",
    "uf": "SP",
    "cep": "01234-567",
    "consumidor_final": "S"
  },
  "produtos": [
    {
      "id": 456,
      "nome": "Caneta Azul",
      "ncm": "39269090",
      "cest": "28.038.00",
      "origem": 0,
      "quantidade": 10,
      "valor_unitario": 12.50,
      "unidade": "UN"
    }
  ],
  "configuracoes": {
    "cfop": "5102",
    "ambiente": "producao",
    "serie": "1"
  }
}
```

### **2. SERVIDOR HTTP NO DELPHI**

#### **Componentes Necessários:**
- `TIdHTTPServer` - Servidor HTTP
- `TIdHTTPResponseInfo` - Resposta HTTP
- `TJSONObject` - Processamento JSON
- `TFDConnection` - Conexão Firebird

#### **Estrutura de Dados:**
```delphi
type
  TClienteNFe = record
    ID: Integer;
    Nome: string;
    CPFCNPJ: string;
    Endereco: string;
    Cidade: string;
    UF: string;
    CEP: string;
    ConsumidorFinal: Boolean;
  end;

  TProdutoNFe = record
    ID: Integer;
    Nome: string;
    NCM: string;
    CEST: string;
    Origem: Integer;
    Quantidade: Double;
    ValorUnitario: Currency;
    Unidade: string;
  end;
```

---

## 🔧 **IMPLEMENTAÇÃO TÉCNICA**

### **1. Servidor HTTP Delphi**
```delphi
procedure TForm1.ServerHTTPRequest(Sender: TObject; ARequest: TIdHTTPRequestInfo; AResponse: TIdHTTPResponseInfo);
var
  JsonData: TJSONObject;
  Cliente: TClienteNFe;
  Produtos: TArray<TProdutoNFe>;
  Resultado: TJSONObject;
begin
  try
    // Recebe dados do Laravel
    JsonData := TJSONObject.ParseJSONValue(ARequest.PostStream) as TJSONObject;
    
    // Converte JSON para estruturas Delphi
    Cliente := ConverterCliente(JsonData);
    Produtos := ConverterProdutos(JsonData);
    
    // Emite NFe usando suas bibliotecas existentes
    Resultado := EmitirNFe(Cliente, Produtos);
    
    // Retorna resultado para Laravel
    AResponse.ContentType := 'application/json';
    AResponse.Content := Resultado.ToString;
    
  except
    on E: Exception do
    begin
      AResponse.ResponseCode := 500;
      AResponse.Content := '{"error": "' + E.Message + '"}';
    end;
  end;
end;
```

### **2. Conversão de Dados**
```delphi
function TForm1.ConverterCliente(JsonData: TJSONObject): TClienteNFe;
begin
  Result.ID := JsonData.GetValue<Integer>('id');
  Result.Nome := JsonData.GetValue<string>('nome');
  Result.CPFCNPJ := JsonData.GetValue<string>('cpf_cnpj');
  Result.Endereco := JsonData.GetValue<string>('endereco');
  Result.Cidade := JsonData.GetValue<string>('cidade');
  Result.UF := JsonData.GetValue<string>('uf');
  Result.CEP := JsonData.GetValue<string>('cep');
  Result.ConsumidorFinal := JsonData.GetValue<string>('consumidor_final') = 'S';
end;
```

---

## 📋 **CHECKLIST DE IMPLEMENTAÇÃO**

### **✅ LARAVEL (ERP)**
- [ ] API endpoint `/api/emitir-nfe`
- [ ] Validação de dados do pedido
- [ ] Formatação JSON para Delphi
- [ ] Tratamento de respostas
- [ ] Atualização de status no banco

### **✅ DELPHI (Emissor)**
- [ ] Servidor HTTP na porta 18080
- [ ] Processamento de JSON recebido
- [ ] Conversão para estruturas Delphi
- [ ] Integração com bibliotecas de NFe
- [ ] Retorno de resultado para Laravel

### **✅ BANCO DE DADOS**
- [ ] Campos compatíveis entre Laravel e Firebird
- [ ] Mapeamento correto de tipos
- [ ] Validações de integridade
- [ ] Índices para performance

---

## 🎯 **PRÓXIMOS PASSOS**

### **1. Implementar API no Laravel**
- Criar controller para emissão de NFe
- Validar dados do pedido
- Formatar payload para Delphi

### **2. Implementar Servidor no Delphi**
- Configurar servidor HTTP
- Processar dados recebidos
- Integrar com emissão de NFe

### **3. Testes de Integração**
- Teste de comunicação entre sistemas
- Validação de dados
- Tratamento de erros

### **4. Documentação e Deploy**
- Manual de uso
- Troubleshooting
- Deploy em produção

---

## 📞 **SUPORTE**

Para dúvidas sobre a integração:
- **Laravel**: Analisar logs em `storage/logs/`
- **Delphi**: Verificar console do aplicativo
- **Firebird**: Consultar logs do banco

---

*Documento criado para integração ERP Laravel ↔ Emissor Delphi (Firebird)*  
*Data: Janeiro 2025*  
*Versão: 1.0*




