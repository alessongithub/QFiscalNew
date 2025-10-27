# Emissor Delphi — API HTTP para Emissão de Notas pelo ERP QFiscal

Este guia orienta a configurar um pequeno servidor HTTP no seu emissor Delphi para que o ERP QFiscal (Laravel) solicite emissão de NF-e/NFS-e localmente.

## Objetivo
- Rodar um servidor HTTP local (localhost) que receba JSON do ERP.
- Emitir a nota via ACBr e responder ao ERP com o resultado (número, protocolo, caminhos de XML, mensagens).

## Requisitos
- Delphi (pode rodar em modo Debug inicialmente)
- Indy: TIdHTTPServer
- ACBr: ACBrNFe/ACBrDFe/ACBrValidador (NF-e)
- (Opcional) ACBrNFSe ou sua stack atual para NFS-e

## Comunicação ERP → Delphi
- URL base (config no ERP `.env`): `DELPHI_EMISSOR_URL` (padrão `http://localhost:18080`)
- Endpoints a expor no Delphi:
  - GET `/api/status` → Healthcheck (retornar 200 `{ "ok": true }`)
  - POST `/api/emitir-nfe` → Emissão de NF-e
  - POST `/api/emitir-nfse` → Emissão de NFS-e (opcional)

### Payload esperado (NF-e)
```json
{
  "tipo": "nfe",
  "numero_pedido": "000123",
  "cliente": {
    "id": 10,
    "nome": "Cliente Teste",
    "cpf_cnpj": "12345678909",
    "endereco": "Rua A",
    "numero": "123",
    "complemento": null,
    "bairro": "Centro",
    "cidade": "São Paulo",
    "uf": "SP",
    "cep": "01001000",
    "telefone": "11999999999",
    "email": "cliente@teste.com",
    "consumidor_final": "S",
    "tipo": "JURIDICA"
  },
  "produtos": [
    {
      "id": 99,
      "nome": "Produto X",
      "ncm": "84713012",
      "cest": null,
      "origem": 0,
      "quantidade": 2,
      "valor_unitario": 1500.00,
      "unidade": "UN",
      "valor_total": 3000.00
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

### Resposta (sucesso)
```json
{
  "ok": true,
  "numero": "12345",
  "protocolo": "135230000000000",
  "xml_path": "C:\\XMLs\\NFe\\2025\\01\\NFe12345.xml",
  "mensagem": "NF-e transmitida com sucesso"
}
```

### Resposta (erro)
```json
{
  "ok": false,
  "erro": "Mensagem descritiva do erro"
}
```

## Implementação rápida (Indy)

### Componentes
- `TIdHTTPServer` (porta 18080)
- `ACBrNFe` configurado (certificado, UF, ambiente, caminhos)

### Inicialização
```delphi
procedure TForm1.FormCreate(Sender: TObject);
begin
  IdHTTPServer1.DefaultPort := 18080; // ajuste a porta se necessário
  IdHTTPServer1.Active := True;
end;
```

### Healthcheck (GET /api/status)
```delphi
procedure TForm1.IdHTTPServer1CommandGet(AContext: TIdContext;
  ARequestInfo: TIdHTTPRequestInfo; AResponseInfo: TIdHTTPResponseInfo);
begin
  if SameText(ARequestInfo.Document, '/api/status') then
  begin
    AResponseInfo.ResponseNo := 200;
    AResponseInfo.ContentType := 'application/json';
    AResponseInfo.ContentText := '{"ok": true}';
    Exit;
  end;

  AResponseInfo.ResponseNo := 404;
  AResponseInfo.ContentType := 'application/json';
  AResponseInfo.ContentText := '{"error": "Not found"}';
end;
```

### Emissão NF-e (POST /api/emitir-nfe)
```delphi
uses System.JSON;

procedure TForm1.IdHTTPServer1CommandOther(AContext: TIdContext;
  ARequestInfo: TIdHTTPRequestInfo; AResponseInfo: TIdHTTPResponseInfo);
var
  Json: TJSONObject;
  Resultado: TJSONObject;
begin
  if (ARequestInfo.CommandType = hcPOST) and SameText(ARequestInfo.Document, '/api/emitir-nfe') then
  begin
    try
      Json := TJSONObject.ParseJSONValue(ARequestInfo.PostStream) as TJSONObject;
      try
        // TODO: Converter JSON → estruturas Delphi, montar ACBrNFe, assinar, validar, transmitir.
        Resultado := EmitirNFe(Json); // implementar conforme sua base

        AResponseInfo.ResponseNo := 200;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := Resultado.ToJSON;
      finally
        Json.Free;
      end;
    except
      on E: Exception do
      begin
        AResponseInfo.ResponseNo := 500;
        AResponseInfo.ContentType := 'application/json';
        AResponseInfo.ContentText := '{"ok": false, "erro": "' + E.Message + '"}';
      end;
    end;
    Exit;
  end;
end;
```

> A função `EmitirNFe(Json)` deve: carregar certificado, setar ambiente (homologação/produção), preencher emitente/destinatário/itens/impostos, gerar XML, transmitir e retornar `{ ok, numero, protocolo, xml_path, mensagem }`.

### NFS-e (POST /api/emitir-nfse)
- Idêntico ao fluxo acima, adaptando para seus componentes/provedores de NFS-e.

## Passos essenciais ACBrNFe (resumo)
1. Certificado
   - `NFe.Configuracoes.Certificados.ArquivoPFX := 'caminho.pfx'`
   - `NFe.Configuracoes.Certificados.Senha := 'senha'`
2. Ambiente/UF
   - `NFe.Configuracoes.WebServices.UF := 'SP'`
   - `NFe.Configuracoes.WebServices.Ambiente := taHomologacao` (ou `taProducao`)
3. Arquivos
   - `NFe.Configuracoes.Arquivos.PathNFe := 'C:\\XMLs\\NFe\\'`
   - `NFe.Configuracoes.Arquivos.PathSchemas := 'C:\\Schemas\\'`
4. Montagem da nota (emitente, destinatário, itens, totais, transporte, fatura)
5. `NFe.Assinar; NFe.Validar; NFe.Enviar(1);`
6. Capturar número, protocolo e caminhos de XML para devolver ao ERP

## Segurança e rede
- Mantenha o servidor ouvindo em `localhost` apenas (binding 127.0.0.1).
- Se mudar a porta, ajuste `DELPHI_EMISSOR_URL` no `.env` do ERP.
- Não exponha a porta para a internet.

## Execução em segundo plano
- Em desenvolvimento: rodar dentro do Delphi (Debug) é suficiente.
- Em produção: gere um executável que inicialize minimizado (bandeja) e ative o `TIdHTTPServer` automaticamente.

## Testes rápidos
- GET `http://localhost:18080/api/status` → 200 `{ ok: true }`
- POST `http://localhost:18080/api/emitir-nfe` (via Postman/cURL) com payload de exemplo → 200 `{ ok: true, numero, protocolo, xml_path }`

## Integração com ERP
- No ERP `.env` (se necessário):
```
DELPHI_EMISSOR_URL=http://localhost:18080
```
- Finalize um Pedido/OS e clique em "Emitir NF-e/NFS-e".

## Troubleshooting
- Porta 18080 ocupada → use 18081 e ajuste o `.env` do ERP.
- Certificado inválido → revise caminho/senha/validade do PFX.
- Rejeição SEFAZ → retorne mensagem detalhada no JSON de erro.
- Timeout → verifique antivírus/firewall e logs do Delphi/ERP.

- [ ] `TIdHTTPServer` ouvindo em `localhost:18080`
- [ ] `GET /api/status` responde 200
- [ ] `POST /api/emitir-nfe` processa JSON, emite, responde JSON
- [ ] ACBrNFe configurado (certificado, UF, ambiente, caminhos)
- [ ] Retornos incluem número, protocolo, caminhos de XML
- [ ] `.env` do ERP com `DELPHI_EMISSOR_URL` correto

Referências úteis: `DOCUMENTACAO_INTEGRACAO_NFE.md`, `INTEGRACAO_FIREBIRD_DELPHI.md`.
