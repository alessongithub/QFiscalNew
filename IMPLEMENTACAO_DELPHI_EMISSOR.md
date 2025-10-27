# 🚀 **IMPLEMENTAÇÃO DELPHI EMISSOR - QFISCAL ERP**

## 📋 **RESUMO EXECUTIVO**

Este documento detalha a implementação necessária no **Delphi Emissor** para integrar com o **QFiscal ERP** e emitir Notas Fiscais Eletrônicas (NFe) automaticamente.

---

## 🎯 **OBJETIVO**

Criar um servidor HTTP local no Delphi que:
- Receba requisições JSON do ERP QFiscal
- Processe os dados de cliente e produtos
- Emita NFe via ACBr
- Retorne o resultado para o ERP

---

## 🔧 **REQUISITOS TÉCNICOS**

### **Componentes Necessários:**
- **Delphi** (qualquer versão recente)
- **Indy:** `TIdHTTPServer` (servidor HTTP)
- **ACBr:** `ACBrNFe`, `ACBrDFe`, `ACBrValidador`
- **JSON:** `System.JSON` (Delphi 10+)

### **Porta e URL:**
- **Porta:** 18080
- **URL Base:** `http://localhost:18080`
- **Timeout:** 30 segundos

---

## 📡 **ENDPOINTS NECESSÁRIOS**

### **1. Health Check**
```
GET /api/status
```
**Resposta esperada:**
```json
{
  "ok": true,
  "message": "Emissor Delphi funcionando",
  "timestamp": "2025-01-17T10:30:00Z"
}
```

### **2. Emissão de NFe**
```
POST /api/emitir-nfe
Content-Type: application/json
```

---

## 📦 **PAYLOAD JSON RECEBIDO**

### **Estrutura Completa:**
```json
{
  "tipo": "nfe",
  "numero_pedido": "PED-001",
  "tenant_id": 1,
  "cliente": {
    "id": 10,
    "nome": "Cliente Teste Ltda",
    "cpf_cnpj": "12345678000199",
    "tipo": "JURIDICA",
    "endereco": "Rua das Flores",
    "numero": "123",
    "complemento": "Sala 1",
    "bairro": "Centro",
    "cidade": "São Paulo",
    "uf": "SP",
    "cep": "01001000",
    "telefone": "11999999999",
    "email": "cliente@teste.com",
    "consumidor_final": "CONSUMIDOR FINAL",
    "codigo_municipio": 3550308
  },
  "produtos": [
    {
      "id": 99,
      "nome": "Produto X",
      "codigo_interno": "PROD001",
      "codigo_barras": "7891234567890",
      "ncm": "84713012",
      "cest": null,
      "origem": 0,
      "unidade": "UN",
      "quantidade": 2,
      "valor_unitario": 1500.00,
      "valor_total": 3000.00,
      "cfop": "5102",
      "cst_icms": "102",
      "aliquota_icms": 18.00
    }
  ],
  "configuracoes": {
    "cfop": "5102",
    "ambiente": "homologacao",
    "serie": "1",
    "tipo_nota": "products"
  }
}
```

### **Mapeamento de Campos:**

#### **Cliente (ERP → Firebird):**
| ERP QFiscal | Firebird Delphi | Observação |
|-------------|-----------------|------------|
| `cliente.id` | `ID_PESSOA` | ID do cliente |
| `cliente.nome` | `DS_RAZAOSOCIAL_NOME` | Nome/Razão Social |
| `cliente.cpf_cnpj` | `NR_CNPJ_CPF` | CPF/CNPJ |
| `cliente.tipo` | `FG_TIPOPESSOA` | "PESSOA FÍSICA" ou "JURÍDICA" |
| `cliente.endereco` | `DS_ENDERECO` | Endereço |
| `cliente.numero` | `NR_NUMERO` | Número |
| `cliente.complemento` | `DS_COMPLEMENTO` | Complemento |
| `cliente.bairro` | `DS_BAIRRO` | Bairro |
| `cliente.cidade` | `DS_MUNICIPIO` | Cidade |
| `cliente.uf` | `CD_UF` | UF |
| `cliente.cep` | `NR_CEP` | CEP |
| `cliente.telefone` | `NR_TELEFONE1` | Telefone |
| `cliente.email` | `DS_EMAIL` | Email |
| `cliente.consumidor_final` | `FG_CONSUMIDOR_FINAL` | "CONSUMIDOR FINAL" ou "REVENDA" |
| `cliente.codigo_municipio` | `CD_MUNICIPIO` | Código IBGE |

#### **Produtos (ERP → Firebird):**
| ERP QFiscal | Firebird Delphi | Observação |
|-------------|-----------------|------------|
| `produtos[].id` | `ID_PRODUTO` | ID do produto |
| `produtos[].nome` | `DS_NOME` | Nome do produto |
| `produtos[].codigo_interno` | `CD_INTERNO` | Código interno |
| `produtos[].codigo_barras` | `CD_GTIN` | Código de barras |
| `produtos[].ncm` | `CD_NCM` | NCM (VARCHAR(20)) |
| `produtos[].cest` | `CD_CEST` | CEST (VARCHAR(20)) |
| `produtos[].origem` | `CD_ORIGEM_PRODUTO` | Origem (INTEGER) |
| `produtos[].unidade` | `CD_UNIDADE` | Unidade |
| `produtos[].quantidade` | `QT_PRODUTO` | Quantidade |
| `produtos[].valor_unitario` | `VR_UNITARIO` | Valor unitário |
| `produtos[].valor_total` | `VR_TOTAL` | Valor total |
| `produtos[].cfop` | `CD_CFOP` | CFOP |
| `produtos[].cst_icms` | `CD_CST_ICMS` | CST ICMS |
| `produtos[].aliquota_icms` | `PC_ALIQUOTA_ICMS` | Alíquota ICMS |

---

## 📤 **RESPOSTA ESPERADA**

### **Sucesso:**
```json
{
  "ok": true,
  "numero": "000001",
  "protocolo": "123456789012345",
  "chave_acesso": "12345678901234567890123456789012345678901234",
  "xml_path": "C:\\NFe\\XML\\12345678901234567890123456789012345678901234.xml",
  "pdf_path": "C:\\NFe\\PDF\\12345678901234567890123456789012345678901234.pdf",
  "message": "NFe emitida com sucesso"
}
```

### **Erro:**
```json
{
  "ok": false,
  "erro": "Descrição do erro",
  "codigo": "CODIGO_ERRO",
  "message": "Mensagem detalhada do erro"
}
```

---

## 💻 **IMPLEMENTAÇÃO DELPHI**

### **1. Estrutura do Projeto:**

```pascal
unit MainForm;

interface

uses
  Winapi.Windows, Winapi.Messages, System.SysUtils, System.Classes,
  Vcl.Graphics, Vcl.Controls, Vcl.Forms, Vcl.Dialogs, Vcl.StdCtrls,
  IdHTTPServer, IdContext, IdCustomHTTPServer, IdHTTPWebBrokerBridge,
  System.JSON, ACBrNFe, ACBrDFe, ACBrValidador;

type
  TFormMain = class(TForm)
    IdHTTPServer: TIdHTTPServer;
    ACBrNFe: TACBrNFe;
    btnStart: TButton;
    btnStop: TButton;
    memoLog: TMemo;
    procedure FormCreate(Sender: TObject);
    procedure btnStartClick(Sender: TObject);
    procedure btnStopClick(Sender: TObject);
    procedure IdHTTPServerCommandGet(AContext: TIdContext;
      ARequestInfo: TIdHTTPRequestInfo; AResponseInfo: TIdHTTPResponseInfo);
  private
    procedure HandleStatus(AContext: TIdContext; ARequestInfo: TIdHTTPRequestInfo; 
      AResponseInfo: TIdHTTPResponseInfo);
    procedure HandleEmitirNFe(AContext: TIdContext; ARequestInfo: TIdHTTPRequestInfo; 
      AResponseInfo: TIdHTTPResponseInfo);
    function ProcessNFeRequest(const JSONData: string): string;
  public
  end;

var
  FormMain: TFormMain;

implementation
```

### **2. Configuração do Servidor:**

```pascal
procedure TFormMain.FormCreate(Sender: TObject);
begin
  IdHTTPServer.DefaultPort := 18080;
  IdHTTPServer.Active := False;
  
  // Configurar ACBr
  ACBrNFe.Configuracoes.WebServices.UF := 'SP';
  ACBrNFe.Configuracoes.WebServices.Ambiente := taHomologacao;
  
  memoLog.Lines.Add('Servidor configurado na porta 18080');
end;

procedure TFormMain.btnStartClick(Sender: TObject);
begin
  IdHTTPServer.Active := True;
  btnStart.Enabled := False;
  btnStop.Enabled := True;
  memoLog.Lines.Add('Servidor iniciado em http://localhost:18080');
end;

procedure TFormMain.btnStopClick(Sender: TObject);
begin
  IdHTTPServer.Active := False;
  btnStart.Enabled := True;
  btnStop.Enabled := False;
  memoLog.Lines.Add('Servidor parado');
end;
```

### **3. Tratamento das Requisições:**

```pascal
procedure TFormMain.IdHTTPServerCommandGet(AContext: TIdContext;
  ARequestInfo: TIdHTTPRequestInfo; AResponseInfo: TIdHTTPResponseInfo);
begin
  AResponseInfo.ContentType := 'application/json';
  AResponseInfo.CharSet := 'utf-8';
  
  try
    if ARequestInfo.URI = '/api/status' then
      HandleStatus(AContext, ARequestInfo, AResponseInfo)
    else if ARequestInfo.URI = '/api/emitir-nfe' then
      HandleEmitirNFe(AContext, ARequestInfo, AResponseInfo)
    else
    begin
      AResponseInfo.ResponseNo := 404;
      AResponseInfo.ContentText := '{"ok": false, "erro": "Endpoint não encontrado"}';
    end;
  except
    on E: Exception do
    begin
      AResponseInfo.ResponseNo := 500;
      AResponseInfo.ContentText := '{"ok": false, "erro": "' + E.Message + '"}';
    end;
  end;
end;
```

### **4. Endpoint Status:**

```pascal
procedure TFormMain.HandleStatus(AContext: TIdContext; ARequestInfo: TIdHTTPRequestInfo; 
  AResponseInfo: TIdHTTPResponseInfo);
var
  ResponseJSON: TJSONObject;
begin
  ResponseJSON := TJSONObject.Create;
  try
    ResponseJSON.AddPair('ok', TJSONBool.Create(True));
    ResponseJSON.AddPair('message', 'Emissor Delphi funcionando');
    ResponseJSON.AddPair('timestamp', DateTimeToStr(Now));
    
    AResponseInfo.ContentText := ResponseJSON.ToString;
    AResponseInfo.ResponseNo := 200;
  finally
    ResponseJSON.Free;
  end;
end;
```

### **5. Endpoint Emitir NFe:**

```pascal
procedure TFormMain.HandleEmitirNFe(AContext: TIdContext; ARequestInfo: TIdHTTPRequestInfo; 
  AResponseInfo: TIdHTTPResponseInfo);
var
  ResponseText: string;
begin
  if ARequestInfo.CommandType <> hcPOST then
  begin
    AResponseInfo.ResponseNo := 405;
    AResponseInfo.ContentText := '{"ok": false, "erro": "Método não permitido"}';
    Exit;
  end;
  
  ResponseText := ProcessNFeRequest(ARequestInfo.PostStream.DataString);
  AResponseInfo.ContentText := ResponseText;
  AResponseInfo.ResponseNo := 200;
end;
```

### **6. Processamento da NFe:**

```pascal
function TFormMain.ProcessNFeRequest(const JSONData: string): string;
var
  JSONObj, ClienteObj, ProdutosArray: TJSONObject;
  ProdutoObj: TJSONObject;
  i: Integer;
  ResponseJSON: TJSONObject;
  NumeroNFe, Protocolo, ChaveAcesso: string;
  XMLPath, PDFPath: string;
begin
  try
    JSONObj := TJSONObject.ParseJSONValue(JSONData) as TJSONObject;
    
    // Extrair dados do cliente
    ClienteObj := JSONObj.GetValue('cliente') as TJSONObject;
    
    // Extrair produtos
    ProdutosArray := JSONObj.GetValue('produtos') as TJSONObject;
    
    // TODO: Implementar emissão via ACBr
    // 1. Configurar dados do emitente
    // 2. Configurar dados do destinatário
    // 3. Adicionar produtos
    // 4. Emitir NFe
    // 5. Gerar XML e PDF
    
    // Simulação de resposta de sucesso
    ResponseJSON := TJSONObject.Create;
    ResponseJSON.AddPair('ok', TJSONBool.Create(True));
    ResponseJSON.AddPair('numero', '000001');
    ResponseJSON.AddPair('protocolo', '123456789012345');
    ResponseJSON.AddPair('chave_acesso', '12345678901234567890123456789012345678901234');
    ResponseJSON.AddPair('xml_path', 'C:\NFe\XML\12345678901234567890123456789012345678901234.xml');
    ResponseJSON.AddPair('pdf_path', 'C:\NFe\PDF\12345678901234567890123456789012345678901234.pdf');
    ResponseJSON.AddPair('message', 'NFe emitida com sucesso');
    
    Result := ResponseJSON.ToString;
    ResponseJSON.Free;
    
  except
    on E: Exception do
    begin
      ResponseJSON := TJSONObject.Create;
      ResponseJSON.AddPair('ok', TJSONBool.Create(False));
      ResponseJSON.AddPair('erro', E.Message);
      ResponseJSON.AddPair('message', 'Erro ao processar NFe');
      
      Result := ResponseJSON.ToString;
      ResponseJSON.Free;
    end;
  end;
end;
```

---

## 🔧 **CONFIGURAÇÃO ACBR**

### **1. Configurações Básicas:**

```pascal
procedure ConfigurarACBr;
begin
  // Configurações do Emitente
  ACBrNFe.Configuracoes.Emitente.CNPJCPF := '12345678000199';
  ACBrNFe.Configuracoes.Emitente.xNome := 'EMPRESA TESTE LTDA';
  ACBrNFe.Configuracoes.Emitente.xFant := 'EMPRESA TESTE';
  ACBrNFe.Configuracoes.Emitente.IE := '123456789';
  ACBrNFe.Configuracoes.Emitente.IEST := '';
  ACBrNFe.Configuracoes.Emitente.IM := '123456';
  ACBrNFe.Configuracoes.Emitente.CNAE := '1234567';
  
  // Endereço do Emitente
  ACBrNFe.Configuracoes.Emitente.EnderEmit.xLgr := 'Rua Teste';
  ACBrNFe.Configuracoes.Emitente.EnderEmit.nro := '123';
  ACBrNFe.Configuracoes.Emitente.EnderEmit.xCpl := '';
  ACBrNFe.Configuracoes.Emitente.EnderEmit.xBairro := 'Centro';
  ACBrNFe.Configuracoes.Emitente.EnderEmit.cMun := 3550308;
  ACBrNFe.Configuracoes.Emitente.EnderEmit.xMun := 'SAO PAULO';
  ACBrNFe.Configuracoes.Emitente.EnderEmit.UF := 'SP';
  ACBrNFe.Configuracoes.Emitente.EnderEmit.CEP := '01001000';
  ACBrNFe.Configuracoes.Emitente.EnderEmit.cPais := 1058;
  ACBrNFe.Configuracoes.Emitente.EnderEmit.xPais := 'BRASIL';
  
  // Configurações WebServices
  ACBrNFe.Configuracoes.WebServices.UF := 'SP';
  ACBrNFe.Configuracoes.WebServices.Ambiente := taHomologacao;
  ACBrNFe.Configuracoes.WebServices.Visualizar := False;
  ACBrNFe.Configuracoes.WebServices.Salvar := True;
  ACBrNFe.Configuracoes.WebServices.PathSalvar := 'C:\NFe\';
end;
```

### **2. Certificado Digital:**

```pascal
procedure ConfigurarCertificado;
begin
  ACBrNFe.Configuracoes.Certificados.ArquivoPFX := 'C:\Certificados\certificado.pfx';
  ACBrNFe.Configuracoes.Certificados.Senha := 'senha123';
  ACBrNFe.Configuracoes.Certificados.NumeroSerie := '';
end;
```

---

## 🧪 **TESTE DE INTEGRAÇÃO**

### **1. Teste Manual com Curl:**

```bash
# Teste de Status
curl http://localhost:18080/api/status

# Teste de Emissão
curl -X POST http://localhost:18080/api/emitir-nfe \
  -H "Content-Type: application/json" \
  -d '{
    "tipo": "nfe",
    "numero_pedido": "TESTE-001",
    "tenant_id": 1,
    "cliente": {
      "id": 1,
      "nome": "Cliente Teste",
      "cpf_cnpj": "12345678909",
      "tipo": "PESSOA FÍSICA",
      "endereco": "Rua Teste",
      "numero": "123",
      "bairro": "Centro",
      "cidade": "São Paulo",
      "uf": "SP",
      "cep": "01001000",
      "telefone": "11999999999",
      "email": "teste@teste.com",
      "consumidor_final": "CONSUMIDOR FINAL",
      "codigo_municipio": 3550308
    },
    "produtos": [
      {
        "id": 1,
        "nome": "Produto Teste",
        "codigo_interno": "PROD001",
        "ncm": "84713012",
        "origem": 0,
        "unidade": "UN",
        "quantidade": 1,
        "valor_unitario": 100.00,
        "valor_total": 100.00,
        "cfop": "5102",
        "cst_icms": "102",
        "aliquota_icms": 18.00
      }
    ],
    "configuracoes": {
      "cfop": "5102",
      "ambiente": "homologacao",
      "serie": "1",
      "tipo_nota": "products"
    }
  }'
```

### **2. Teste pelo ERP:**

1. Acesse o ERP QFiscal
2. Vá para o menu "NFe"
3. Clique em "Emitir NFe"
4. Preencha os dados
5. Clique em "Emitir NFe"
6. Verifique o resultado

---

## 📋 **CHECKLIST DE IMPLEMENTAÇÃO**

### **✅ Básico:**
- [ ] Servidor HTTP na porta 8080
- [ ] Endpoint `/api/status` funcionando
- [ ] Endpoint `/api/emitir-nfe` funcionando
- [ ] Tratamento de JSON
- [ ] Resposta em formato correto

### **✅ ACBr:**
- [ ] Configuração do emitente
- [ ] Certificado digital configurado
- [ ] WebServices configurados
- [ ] Emissão de NFe funcionando
- [ ] Geração de XML e PDF

### **✅ Integração:**
- [ ] Mapeamento de campos cliente
- [ ] Mapeamento de campos produtos
- [ ] Tratamento de erros
- [ ] Logs de emissão
- [ ] Teste com ERP

---

## 🚨 **TRATAMENTO DE ERROS**

### **Erros Comuns:**

1. **Certificado Inválido:**
   ```json
   {
     "ok": false,
     "erro": "Certificado digital inválido",
     "codigo": "CERT_INVALIDO"
   }
   ```

2. **Dados Inválidos:**
   ```json
   {
     "ok": false,
     "erro": "CPF/CNPJ inválido",
     "codigo": "DADOS_INVALIDOS"
   }
   ```

3. **WebService Indisponível:**
   ```json
   {
     "ok": false,
     "erro": "WebService SEFAZ indisponível",
     "codigo": "WS_INDISPONIVEL"
   }
   ```

---

## 📞 **SUPORTE**

**Para dúvidas ou problemas:**
- **Email:** contato@qfiscal.com.br
- **WhatsApp:** 947146126
- **Documentação ACBr:** https://acbr.sourceforge.io/

---

*Documento criado em Janeiro 2025*  
*Versão: 1.0*  
*Compatível com QFiscal ERP v1.0*
