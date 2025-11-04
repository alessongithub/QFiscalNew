# Repasse e Boletos (Mercado Pago)

## Visão Geral
- Conta global do Mercado Pago configurada em `Admin > Gateway`
- Boletos emitidos via `Recebíveis > Emitir boleto`
- Após pagamento e liquidação, saldo líquido (taxa MP + 1%) aparece em `Tenant > Saldo`
- Tenant solicita transferência; repasse é efetuado manualmente por você

## Webhook
- Endpoint: `POST /webhooks/mercadopago`
- Atualiza `Receivable` para `paid` e cria `TenantBalance` com status `pending`

## Saldo do Tenant
- Rota: `GET /tenant/balance`
- Cálculo do saldo líquido: `bruto − taxa_MP − 1%`
- Status: `pending → available → requested → transferring → transferred`
- Verificação de liquidação: job a cada 6h (`routes/console.php`)

## Telas e Fluxos
- `Recebíveis > Criar`: cria o título (não emite boleto automaticamente)
- `Recebíveis > Listagem`: botão “Emitir boleto” por título
- Filtro de boletos: `GET /receivables?has_boleto=1`
  - Baixa em lote desabilitada nesse filtro

## Tabelas
- `tenant_balances`: controle de saldos por recebível
- `tenant_transfer_settings`: dados bancários/PIX do tenant

## Jobs
- `CheckBalanceAvailability`: marca saldo como disponível quando liquidado
- `ProcessTransfer`: efetua marcação de transferência (processo manual/API)

## Próximos Passos
- (Opcional) Transferência automática via API do MP
- (Opcional) Configurar taxa de plataforma por plano/tenant
- (Futuro) OAuth para conta por tenant (split marketplace)
