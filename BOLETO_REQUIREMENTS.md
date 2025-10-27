# Requisitos para Emissão de Boleto (Mercado Pago)

Este documento registra os requisitos e campos necessários identificados para emissão de boletos via Mercado Pago (Checkout API) e o que já foi implementado no sistema.

## Campos obrigatórios no payload

- transaction_amount: número (mínimo R$ 4,00; recomendamos >= R$ 10,00 nos testes)
- description: texto
- payment_method_id: "bolbradesco"
- external_reference: string única (usamos `rec_{id}`)
- date_of_expiration: data no formato `YYYY-MM-DDTHH:mm:ss.000-03:00`
- payer:
  - email: e-mail do cliente
  - first_name: primeiro nome do cliente
  - last_name: sobrenome do cliente
  - identification:
    - type: "CPF" (ou "CNPJ" conforme o caso)
    - number: CPF/CNPJ apenas dígitos
  - address:
    - zip_code: CEP (apenas dígitos)
    - street_name: Logradouro
    - street_number: Número
    - neighborhood: Bairro
    - city: Cidade
    - federal_unit: UF (ex: SP)

## Cabeçalhos HTTP obrigatórios

- Authorization: `Bearer {ACCESS_TOKEN}`
- Content-Type: `application/json`
- X-Idempotency-Key: valor único por tentativa (ex.: `rec_{id}_{timestamp}`)

## Multa e Juros

- Multa (%): até 2% (lei brasileira)
- Juros ao mês (%): até 1%/mês (~0,033%/dia)
- Implementado no controller para incluir `additional_info` e `fee_details` quando valores > 0

## Validações realizadas no sistema

- Verifica se o cliente possui: Nome, CPF/CNPJ, E-mail, Endereço, Cidade, Estado
- Normalização de CPF/CNPJ/CEP (apenas dígitos)
- Geração do `external_reference` com prefixo `rec_`
- Formatação de `date_of_expiration` com timezone `-03:00`
- Envio de e-mail com link do boleto após emissão (usando SMTP ativo)

## Persistência no Receivable

- Campos: `boleto_mp_id`, `boleto_url`, `boleto_pdf_url`, `boleto_barcode`, `boleto_emitted_at`
- Ajuste do `due_date` quando alterado no modal

## Webhook

- `/webhooks/mercadopago`: reconhece `external_reference` com prefixo `rec_`
- Ao `approved`, marca `Receivable` como `paid` e define `received_at`

## Observações do Sandbox

- Mesmo com payload válido, o sandbox pode retornar `500 internal_error` em contas que não possuem boleto habilitado
- Verificar no painel do MP se boletos estão ativos para o aplicativo
- Alternativas de teste: usar PIX (payment_type_id bank_transfer) ou validar com suporte do MP

## Pontos futuros (opcional)

- Tela dedicada de "Boletos" com reenvio por e-mail/WhatsApp e 2ª via
- Regras de reemissão com novo vencimento e cálculo de juros pró-rata
- Tarefas automáticas de lembrete (D-3, D-0, D+1)
