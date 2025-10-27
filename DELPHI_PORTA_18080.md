# Ajuste de Porta 18080 no Emissor Delphi

## Objetivo
Padronizar a integração para a porta 18080 entre o ERP QFiscal (Laravel) e o Emissor Delphi.

## Passo 1 — Delphi (TIdHTTPServer)
1. Abra o projeto do Emissor no Delphi.
2. Localize a configuração do `TIdHTTPServer` (geralmente no `FormCreate`).
3. Altere a porta:

```delphi
procedure TForm1.FormCreate(Sender: TObject);
begin
  IdHTTPServer1.DefaultPort := 18080;
  IdHTTPServer1.OnCommandGet := IdHTTPServer1CommandGet;
  IdHTTPServer1.OnCommandOther := IdHTTPServer1CommandGet; // garante POST
  IdHTTPServer1.Active := True;
end;
```

4. Certifique-se de que as rotas existem:
   - GET `/api/status` → retorna 200 `{ "ok": true }`
   - POST `/api/emitir-nfe` → processa JSON e retorna `{ ok, numero, protocolo, xml_path, ... }`

## Passo 2 — ERP (Laravel)
1. Edite o `.env` do ERP e ajuste a URL base:

```env
DELPHI_EMISSOR_URL=http://127.0.0.1:18080
DELPHI_EMISSOR_TIMEOUT=60
```

2. Limpe o cache de configuração:

```bash
php artisan config:clear
```

## Passo 3 — Testes rápidos
- Status:

```bash
curl http://127.0.0.1:18080/api/status
```

- Emissão (exemplo mínimo):

```bash
curl -X POST http://127.0.0.1:18080/api/emitir-nfe \
  -H "Content-Type: application/json" \
  -d '{"tipo":"nfe","numero_pedido":"TESTE-001","cliente":{},"produtos":[],"configuracoes":{}}'
```

Se responder 404, confira: rota exata, método POST, `OnCommandOther` ligado e servidor ativo.

## Observações
- 18080 é menos concorrida que 8080 no Windows (evita conflitos com proxies/Apache).
- Mantenha o binding em `localhost` (127.0.0.1). Não exponha a porta publicamente.
- Caso use Docker/WSL para o ERP, chame `http://host.docker.internal:18080`.

## Tratamento de erros (Delphi): evitar timeout no ERP
- No endpoint `POST /api/emitir-nfe`, capture erros de certificado/validação/transmissão e responda imediatamente com JSON. Assim o ERP não estoura timeout.

```delphi
try
  // Configurar e validar certificado (PFX/Serial)
  ConfigurarCertificado;
except
  on E: Exception do
  begin
    AResponseInfo.ResponseNo := 400;
    AResponseInfo.ContentType := 'application/json; charset=utf-8';
    AResponseInfo.ContentText := '{"ok":false,"erro":"Certificado inválido/ausente: ' +
      StringReplace(E.Message, '"','\"',[rfReplaceAll]) + '"}';
    Exit;
  end;
end;

try
  // Assinar, validar, transmitir via ACBr
  // ... montar NFe, ACBrNFe.Assinar; Validar; Enviar(1);
  // montar JSON de sucesso com numero/protocolo/xml_path
except
  on E: Exception do
  begin
    AResponseInfo.ResponseNo := 500;
    AResponseInfo.ContentType := 'application/json; charset=utf-8';
    AResponseInfo.ContentText := '{"ok":false,"erro":"' +
      StringReplace(E.Message, '"','\"',[rfReplaceAll]) + '"}';
    Exit;
  end;
end;
```

## Abrir o Emissor automaticamente (futuro)
- Comportamento desejado no ERP: se `GET /api/status` falhar, exibir mensagem informando que o Emissor não está em execução e perguntar se deseja abrir.
- Ao confirmar, o ERP tentará iniciar o executável do Emissor (ex.: `C:\\Program Files\\QFiscal Emissor\\Emissor.exe`), com o caminho configurável em `.env` ou `settings`.
- Implementaremos quando o executável/pasta estiverem definidos. Por ora, mantenha o Emissor aberto em segundo plano para emissão direta.


