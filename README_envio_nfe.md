# Guia rápido: Enviar NFe via API do Emissor (PowerShell/curl)

## 1) Pré-requisitos
- Servidor do Emissor Delphi rodando em `http://127.0.0.1:18080`
- Token: `qfiscal_default_token_2025`
- Arquivo de payload: `C:\temp\payload.json` (UTF-8)

## 2) Validar o JSON do arquivo
```powershell
Test-Path 'C:\temp\payload.json'
(Get-Content 'C:\temp\payload.json' -Raw -Encoding UTF8) | ConvertFrom-Json | Out-Null; 'JSON OK'
```

## 3) Enviar usando PowerShell (recomendado)
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:18080/api/emitir-nfe?token=qfiscal_default_token_2025" -Method POST -ContentType "application/json; charset=utf-8" -InFile "C:\temp\payload.json"
```

## 4) Alternativa com curl (Windows nativo)
```powershell
curl.exe -sS -X POST ^
  -H "Content-Type: application/json; charset=utf-8" ^
  --data-binary "@C:\temp\payload.json" ^
  "http://127.0.0.1:18080/api/emitir-nfe?token=qfiscal_default_token_2025"
```

## 5) Ver logs e pré-XML retornado
A resposta JSON pode conter `pre_xml_path`. Para inspecionar nós-chave:
```powershell
$px = '<cole_aqui_o_pre_xml_path_da_resposta>'
Get-Content $px -Raw | Select-String -Pattern '<ide>|<emit>|<CRT>|<total>|<ICMSTot>|<pag>|<detPag>|<tPag>|<vPag>' -SimpleMatch
```

Se o arquivo alternativo foi gerado:
```powershell
Get-Content 'C:\temp\pre_envio_latest.xml' -Raw | Select-String -Pattern '<ide>|<emit>|<CRT>|<total>|<ICMSTot>|<pag>|<detPag>|<tPag>|<vPag>' -SimpleMatch
```

## 6) Dicas
- Sempre use `-InFile` ou bytes UTF-8 para evitar problemas de encoding no PowerShell 5.1.
- Se houver erro "JSON inválido", confirme que o arquivo existe e está em UTF-8.
- Se houver "XmlNode não pode ser nulo.", envie o `pre_xml_path` e/ou o XML para diagnóstico de nós obrigatórios.
