## QFiscal – Roadmap e Pendências

### Autenticação, Usuários e RBAC
- Revisar fluxo de criação de usuários (técnico, operador, gestor) garantindo `tenant_id` e senha definidos para login em `/login`.
- Tela de gestão de papéis e permissões (criar/editar/remover papéis, atribuir permissões em UI).
- Impersonação segura (admin consegue “entrar como” um usuário de um tenant com trilha de auditoria).
- Auditoria de ações (logs por usuário/tenant nas principais operações).

### Multi‑tenancy
- Opcional: isolar dados via conexão por tenant (Database per tenant ou Schema per tenant) e `TenantMiddleware` configurando conexão.
- Reforçar escopo por `tenant_id` em todos os controllers/queries (já majoritariamente feito, revisar).

### Planos, Assinaturas e Cobrança
- Integração de gateway de pagamento (PIX/Cartão): rota de checkout, webhooks, reconciliação.
- Assinaturas (subscriptions):
  - Gerar invoice automático mensal ao virar período.
  - Atualizar `status` do tenant/subscription conforme pagamento.
  - Fluxo de upgrade/downgrade de plano (pro‑rata opcional).
- Scheduler (cron):
  - D−5: notificação de lembrete de pagamento.
  - D+1: alerta de atraso.
  - D+3: suspensão automática do acesso (reversível ao pagar).
- UI do tenant: página “Assinatura/Financeiro” com histórico, 2ª via, geração de boleto/PIX (placeholder até gateway real).
- Admin: tela de cobrança completa (filtrar por status, marcar pago, reemitir, exportar CSV/PDF).

### Financeiro (A Receber / A Pagar / Caixa)
- Recebíveis/Payables: anexos de comprovantes, estornos, remarcação de vencimento, baixas parciais.
- Caixa do dia: fechamento com sangria/suprimento, relatórios por período; conciliação com recebíveis pagos.
- Relatórios financeiros: DRE simplificada, fluxo de caixa projetado.

### Serviços (OS), Vendas (Orçamentos/Pedidos), Produtos/Estoque
- OS: parcelamento nativo na finalização; geração automática de recebíveis por parcela; emissão de NFS‑e (quando disponível).
- Orçamentos/Pedidos:
  - PDF/Impressão com layout profissional (logo, rodapé, condições).
  - Conversão orçamento→pedido com status e trilha.
  - Integração de pedidos com estoque (baixa automática e reservas).
- Estoque: editar/estornar movimentos, inventário (contagem), curva ABC.
- Produtos: variantes/grades (opcional), custo médio, fornecedores.

### Calendário e Tarefas
- Melhorar calendário: eventos recorrentes, lembretes por e‑mail/WhatsApp (integração futura), arrastar/soltar.

### Relatórios e Impressões
- Relatórios adicionais (clientes, produtos, serviços, vendas) com filtros avançados e exportação (PDF/CSV/XLSX).
- Motor de templates de impressão (Blade dedicado) e geração de PDFs (dompdf/snappy) padronizada.

### Admin (Backoffice)
- Notícias/Novidades: permissões específicas para gerenciar; publicação programada; ordenação/arquivo.
- Plans: edição visual dos recursos (`features`) com validações e pré‑visualização.
- Cobranças: dashboard com KPIs (MRR, churn, inadimplência, LTV, ARPU).

### UX, i18n, Acessibilidade
- Revisar todos os textos para pt‑BR; mensagens de erro mais amigáveis.
- Componentes consistentes (inputs, tabelas, modais) e estados vazios.
- Acessibilidade: navegação por teclado, contrastes, labels.

### E‑mail e Notificações
- Templates de e‑mail (Blade) unificados: ativação, cobrança, lembretes, reset de senha.
- Notificações in‑app (toasts/banners) e por e‑mail; fila de jobs.

### Segurança, Performance e Infra
- Políticas de autorização granular em rotas (`can:`) além de checagens em views.
- Proteção contra N+1 (uso consistente de `with()`), caches leves em listas.
- Backups (banco e storage), logs estruturados, monitoração de erros (Sentry).

### Testes e Qualidade
- Testes de feature para fluxos críticos (login, CRUDs, finalização OS, conversões, filtros/relatórios).
- Testes de unidade para regras de negócio (limites por plano, cálculos de totais, vencimentos).

### Contabilidade (futuro)
- Módulo contábil (apenas clientes contábeis): exportações (SPED/DF), integrações, plano de contas e lançamentos.

---
Itens prioritários sugeridos: gateway + scheduler de cobrança, persistência de dados do tenant no `/profile`, UI de permissões, PDFs padronizados, conciliação estoque‑vendas, e testes de regressão.

