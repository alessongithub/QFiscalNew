# Integração ERP QFiscal ↔ Emissor Delphi (NF-e/NFS-e)

Este documento descreve o que o ERP QFiscal precisa enviar/esperar para que a emissão de notas no Emissor Delphi funcione perfeitamente, além de opções de fluxo (100% automático ou com tela para conferência).

## Visão geral da integração

Existem 2 modos de integração possíveis. Escolha um (ou habilite ambos):

- Modo A — Emissão direta (headless)
  - O ERP envia um JSON para o endpoint local do Emissor Delphi.
  - O Emissor monta a NF-e via ACBr, assina, valida, transmite e retorna número/protocolo/caminho do XML.
  - Não abre telas. Ideal para emissão automática a partir do ERP.

- Modo B — Preparar tela para conferência (UI)
  - O ERP envia os dados do pedido e solicita que o Emissor abra a tela de emissão já preenchida para conferência do usuário.
  - O usuário confere/ajusta e clica para emitir. Depois, salvamos e devolvemos status para o ERP.
  - Exige um endpoint adicional (ver seção “Endpoints opcionais: UI”).

## Requisitos do ERP (gerais)

- Comunicação HTTP local
  - Base URL (no ERP): `DELPHI_EMISSOR_URL` (padrão `http://localhost:18080`).
  - Recomendado chamar apenas localmente (mesma máquina do emissor). Não expor na Internet.

- Timeout e retentativas
  - Timeout de requisição: 60s (transmissão pode levar alguns segundos).
  - Em caso de falha de rede pontual, retentar 1-2 vezes.

- Idempotência
  - Enviar um identificador único por pedido (ex.: `numero_pedido` + `tenant_id`).
  - O Emissor pode recusar duplicatas (HTTP 409) ou retornar o mesmo resultado se já emitido.

- Segurança (opcional)
  - Podemos habilitar `Authorization: Bearer <TOKEN>` e validar no Delphi.

## Endpoints (atuais)

- GET `/api/status`
  - Healthcheck. Retorna 200 `{ "ok": true }`.

- POST `/api/emitir-nfe`
  - Emite NF-e de forma direta (Modo A). Entrada/saída abaixo.

### Request — `POST /api/emitir-nfe`

Campos mínimos para funcionar; quanto mais completo, melhor a fidelidade fiscal. Exemplo:

```json
{
  "tenant_id": "acme-001",
  "numero_pedido": "000123",
  "emissor_id": 1,
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
    "tipo": "JURIDICA",
    "consumidor_final": "S"
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
      "valor_total": 3000.00,
      "cfop": "5102",
      "cst_icms": "00",
      "csosn": null,
      "p_icms": 18.0,
      "cst_pis": "01",
      "p_pis": 0.65,
      "cst_cofins": "01",
      "p_cofins": 3.0,
      "cst_ipi": null,
      "p_ipi": 0
    }
  ],
  "configuracoes": {
    "ambiente": "homologacao",
    "serie": "1",
    "tipo_nota": "Venda de mercadorias"
  },
  "transporte": {
    "modalidade": "SEM FRETE"
  },
  "pagamentos": [
    { "tipo": "PIX", "valor": 3000.00 }
  ],
  "observacoes": {
    "inf_complementar": "Texto livre",
    "inf_fisco": ""
  }
}
```

Observações importantes:
- Se o ERP não enviar CST/CSOSN/PIS/COFINS/IPI, o Emissor pode aplicar defaults (ICMS isento para regime normal; CSOSN 102 para Simples). Isso é apenas para testes; em produção, envie as regras ou permita que o Emissor busque do cadastro (por `id` do produto).
- `emissor_id` é opcional se já existir “Emissor padrão” configurado no Delphi.

### Response — sucesso

```json
{
  "ok": true,
  "numero": "12345",
  "protocolo": "135230000000000",
  "xml_path": "C:\\XMLs\\NFe\\2025\\01\\NFe12345.xml",
  "mensagem": "NF-e transmitida com sucesso"
}
```

### Response — erro

```json
{ "ok": false, "erro": "Mensagem descritiva do erro" }
```

### Erros comuns (códigos)
- 400: JSON inválido / campos obrigatórios ausentes (ex.: cliente/produtos).
- 409: Duplicidade (mesmo `numero_pedido`/chave já emitida).
- 422: Regras fiscais inconsistentes (CFOP/CST/aliquotas).
- 500: Certificado inválido/expirado; falha na SEFAZ; erro interno.

## Endpoints adicionais (gestão)

- POST `/api/cancelar-nfe`
  - Body: `{ "xml_path": "C:\\...\\NFe12345.xml", "motivo": "ERRO DE PREENCHIMENTO" }`
  - Retorna: `{ ok, xml_retorno }` (conteúdo XML do retorno do evento)

- POST `/api/carta-correcao`
  - Body: `{ "xml_path": "C:\\...\\NFe12345.xml", "correcao": "Texto com no mínimo 15 caracteres" }`
  - Retorna: `{ ok, xml_retorno }`

- GET `/api/consultar-nfe?xml_path=...`
  - Retorna chave extraída do XML e o próprio `xml_path`.

- GET `/api/download-xml?xml_path=...`
  - Retorna o arquivo XML (content-type `application/xml`).

- GET `/api/imprimir-danfe?xml_path=...`
  - Imprime o DANFE do XML informado (usa ACBrNFeDANFeRL instalado no emissor).

## Endpoints opcionais: UI (Modo B)

Para “preparar a tela para o usuário emitir” (conferência manual):

- POST `/api/preparar-emissao-nfe` (a implementar)
  - Preenche as tabelas no Firebird com os dados do pedido (em rascunho) e abre a tela `form_cadastro_nfe` já com tudo carregado para o usuário conferir e emitir.
  - Requer os mesmos campos de `emitir-nfe` e mapeamento 1:1 para as tabelas.
  - Resposta: `{ ok: true, id_nota: <ID_NOTAFISCAL>, mensagem }` e, após o usuário emitir, o ERP pode consultar status ou receber callback (opcional).

Se optarem por esse modo, descrevemos/implementamos o mapeamento de tabelas e chaves (produtos, faturas, pagamentos, frete) com base no que já existe em `unit_conexao_nfe`.

## Persistência e fonte da verdade

- Opção 1 — ERP como fonte da verdade
  - O ERP guarda número, protocolo, chave, caminho do XML e status. O Emissor salva os XMLs em disco.
  - Para reimpressão/cancelamento/CCe, o ERP chama endpoints específicos (acima).

- Opção 2 — Emissor Delphi como fonte
  - O Emissor persiste tudo no Firebird. O ERP armazena um espelho (id da nota, número, protocolo) para exibição e relatórios.
  - Eventos (cancelamento/CCe) podem ser iniciados pelo ERP via endpoints ou pelo próprio Emissor.

## Regras fiscais e mapeamento (recomendação)

- Idealmente, o ERP envia os códigos fiscais prontos (CFOP, CST/CSOSN, alíquotas de ICMS/PIS/COFINS/IPI por item). Assim o XML sai idêntico ao cálculo fiscal do ERP.
- Alternativa: enviar apenas `produto.id` e o Emissor busca do próprio cadastro e aplica as regras. Requer alinhamento de cadastros entre ERP ↔ Emissor.

## Segurança

- O serviço escuta em `127.0.0.1:18080` por padrão.
- Não exponha a porta publicamente.
- Podemos adicionar token de autenticação por header, se desejarem.

## Checklist de Go-Live

- [ ] Emissor padrão configurado no Delphi (PFX, senha, UF, cMun, paths de XML, logo, série/modelo).
- [ ] Certificado válido e funcional (conseguir consultar serviço SEFAZ).
- [ ] ERP com `DELPHI_EMISSOR_URL` apontando para `http://localhost:18080`.
- [ ] Fluxo definido: Modo A (direto) ou Modo B (com conferência na UI).
- [ ] Payload do ERP ajustado para o schema acima (ou mapeamento tabelas pronto, se Modo B).
- [ ] Testes em homologação concluídos (pedido simples, com frete, com desconto, com IPI, etc.).

## Perguntas para definirmos agora

1) Preferem emissão 100% automática (sem abrir tela) ou preparar tela para conferência do usuário?
2) O ERP enviará as regras fiscais por item (CFOP/CST/CSOSN/aliquotas) ou querem que eu busque do cadastro do Emissor?
3) Qual campo(s) usar como idempotência? Sugiro `tenant_id` + `numero_pedido`.
4) Querem autenticação por token no endpoint?
5) Quais próximos endpoints priorizamos (cancelamento, CCe, download XML, impressão DANFE)?
