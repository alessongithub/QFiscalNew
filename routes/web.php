<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\PlanUpgradeController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\DailyCashController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InboundInvoiceController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TaxCreditController;
use App\Http\Controllers\ServiceOrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\NfeController;
use App\Http\Controllers\NcmRuleController;
use App\Http\Controllers\CertificateController;

Route::get('/', [App\Http\Controllers\LandingController::class, 'index'])->name('landing');
Route::get('/parceiros', [App\Http\Controllers\PartnerPublicController::class, 'showForm'])->name('partner.apply');
Route::post('/parceiros', [App\Http\Controllers\PartnerPublicController::class, 'submit'])->name('partner.apply.submit');
Route::get('/parceiros/set-password', [App\Http\Controllers\PartnerAuthController::class, 'showSetPassword'])->name('partner.set_password');
Route::post('/parceiros/set-password', [App\Http\Controllers\PartnerAuthController::class, 'setPassword'])->name('partner.set_password.submit');
Route::get('/parceiros/login', [App\Http\Controllers\PartnerAuthController::class, 'showLogin'])->name('partner.login');
Route::post('/parceiros/login', [App\Http\Controllers\PartnerAuthController::class, 'login'])->name('partner.login.submit');
Route::post('/parceiros/logout', [App\Http\Controllers\PartnerAuthController::class, 'logout'])->name('partner.logout');
Route::get('/parceiros/password', [App\Http\Controllers\PartnerAuthController::class, 'showPasswordForm'])->name('partner.password');
Route::post('/parceiros/password', [App\Http\Controllers\PartnerAuthController::class, 'updatePassword'])->name('partner.password.submit');

// Rotas para registro de tenant (empresa) - 2 etapas
Route::get('/register', [TenantController::class, 'create'])->name('tenant.register');
Route::post('/register', [TenantController::class, 'storeStep1'])->name('tenant.register.step1');
Route::get('/register/step2', [TenantController::class, 'createStep2'])->name('tenant.register.step2');
Route::post('/register/step2', [TenantController::class, 'storeStep2'])->name('tenant.register.step2');
Route::get('/register/completed', function() { return view('tenants.registration-completed'); })->name('tenant.registration.completed');
Route::get('/activate/{user}/{token}', [TenantController::class, 'activateAccount'])->name('tenant.activate');

// Rotas que precisam apenas de autenticação
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Upgrade de plano
    Route::get('/plans/upgrade', [PlanUpgradeController::class, 'showUpgrade'])->name('plans.upgrade');
    Route::post('/plans/upgrade', [PlanUpgradeController::class, 'processUpgrade'])->name('plans.upgrade.process');
});

// Rotas de checkout (apenas autenticado, sem exigir tenant ativo)
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/create', [\App\Http\Controllers\CheckoutController::class, 'createPreference'])->name('checkout.create');
    Route::get('/checkout/success', [\App\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/pending', [\App\Http\Controllers\CheckoutController::class, 'pending'])->name('checkout.pending');
    Route::get('/checkout/failure', [\App\Http\Controllers\CheckoutController::class, 'failure'])->name('checkout.failure');
});

// Rotas que precisam de autenticação e tenant
Route::middleware(['auth', \App\Http\Middleware\TenantMiddleware::class])->group(function () {
    // Rotas para certificados instalados (usadas no /settings)
    Route::prefix('api')->group(function () {
        Route::get('/certificates/installed', [CertificateController::class, 'listInstalledCertificates'])->name('certificates.installed');
        Route::get('/certificates/details', [CertificateController::class, 'getCertificateDetails'])->name('certificates.details');
        Route::get('/certificates/test-powershell', [CertificateController::class, 'testPowerShell'])->name('certificates.test');
        Route::post('/settings/nfe/cert-serial', [\App\Http\Controllers\SettingsController::class, 'updateCertSerial'])->name('settings.nfe.cert_serial');
    });

    // Faturas do Tenant
    Route::get('/billing/invoices', [BillingController::class, 'invoicesIndex'])->name('billing.invoices.index');

    // NFe - Notas Fiscais Eletrônicas
    Route::prefix('nfe')->name('nfe.')->group(function () {
        // Visualização (sempre acessível ao tenant)
        Route::get('/', [NfeController::class, 'index'])->name('index');
        Route::get('/{nfeNote}', [NfeController::class, 'show'])->name('show');
        // Ações de emissão (protegidas por feature do plano)
        Route::middleware(\App\Http\Middleware\PlanFeatureMiddleware::class . ':allow_issue_nfe')->group(function () {
            Route::post('/emitir', [NfeController::class, 'emitir'])->name('emitir');
            Route::post('/{nfeNote}/retry', [NfeController::class, 'retry'])->name('retry');
            // Rota legada (SEM justificativa) — mantida apenas para compatibilidade, não usar na UI
            Route::post('/{nfeNote}/cancel-legacy', [NfeController::class, 'cancel'])->name('cancel_legacy');
        });
        // Ações de download e e-mail
        Route::get('/{nfeNote}/xml', [NfeController::class, 'downloadXml'])->name('xml');
        Route::get('/{nfeNote}/cancel-xml', [NfeController::class, 'downloadCancelXml'])->name('cancel_xml');
            Route::get('/{nfeNote}/cce-xml', [\App\Http\Controllers\NfeController::class, 'downloadCceXml'])->name('cce_xml');
        Route::get('/{nfeNote}/pdf', [NfeController::class, 'downloadPdf'])->name('pdf');
        Route::get('/{nfeNote}/danfe', [NfeController::class, 'generateDanfe'])->name('danfe');
        Route::get('/{nfeNote}/danfe-erp', [NfeController::class, 'generateDanfeErp'])->name('danfe_erp');
        Route::post('/{nfeNote}/email', [NfeController::class, 'sendEmail'])->name('email');
        Route::get('/{nfeNote}/inut-xml', [NfeController::class, 'downloadInutilizacaoXml'])->name('inut_xml');
        // Cancelamento simples (sem justificativa) — legado
        Route::post('/{nfeNote}/cancel-simple', [NfeController::class, 'cancel'])->name('cancel_simple');
    });

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Storage (monitoramento e compra de espaço)
    Route::prefix('storage-management')->group(function () {
        Route::get('/', [App\Http\Controllers\StorageController::class, 'index'])->name('storage.index');
        Route::get('/upgrade', [App\Http\Controllers\StorageController::class, 'upgrade'])->name('storage.upgrade');
        Route::post('/purchase-addon', [App\Http\Controllers\StorageController::class, 'purchaseAddon'])->name('storage.purchase-addon');
    });

    // Rotas de Clientes
    Route::resource('clients', ClientController::class);

    // Usuários (gestão por tenant)
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    // Produtos
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle_active');
    Route::get('/api/products/search', [ProductController::class, 'search'])->name('products.search');
    // Endpoint simples para consultar saldo e tipo do produto no PDV
    Route::get('/api/products/stock/{product}', function(\App\Models\Product $product){
        $tenantId = auth()->user()->tenant_id;
        if ($product->tenant_id !== $tenantId) { abort(403); }
        $entry = (float) \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity');
        $exit = (float) \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->where('type','exit')->sum('quantity');
        $balance = $entry - $exit;
        return response()->json([ 'type' => (string) $product->type, 'balance' => $balance ]);
    })->name('products.stock');
    Route::get('/api/clients/search', [ClientController::class, 'search'])->name('clients.search');

    // Estoque
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/create', [StockController::class, 'create'])->name('stock.create');
    Route::post('/stock', [StockController::class, 'store'])->name('stock.store');
    Route::get('/stock/{movement}/edit', [StockController::class, 'edit'])->name('stock.edit');
    Route::put('/stock/{movement}', [StockController::class, 'update'])->name('stock.update');
    Route::get('/stock/kardex/{product}', [StockController::class, 'kardex'])->name('stock.kardex');
    Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');
    Route::post('/stock/{movement}/reversal', [StockController::class, 'reverse'])->name('stock.reversal');

    // Financeiro - Receber
    Route::resource('receivables', ReceivableController::class)->except(['destroy']);
    Route::post('receivables/{receivable}/receive', [ReceivableController::class, 'receive'])->name('receivables.receive');
    Route::post('receivables/{receivable}/reverse', [ReceivableController::class, 'reverse'])->name('receivables.reverse');
    Route::post('receivables/{receivable}/cancel', [ReceivableController::class, 'cancel'])->name('receivables.cancel');
    Route::post('receivables/bulk-receive', [ReceivableController::class, 'receiveBulk'])->name('receivables.bulk_receive');
    Route::post('receivables/{receivable}/emit-boleto', [ReceivableController::class, 'emitBoleto'])->name('receivables.emit_boleto');
    Route::get('test-mp', function() {
        $config = \App\Models\GatewayConfig::current();
        $token = $config->active_access_token;
        if (!$token) {
            return response()->json(['error' => 'Token não configurado']);
        }
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('https://api.mercadopago.com/v1/payment_methods');
        
        return response()->json([
            'token_prefix' => substr($token, 0, 10) . '...',
            'status' => $response->status(),
            'success' => $response->successful(),
            'response' => $response->json()
        ]);
    })->name('test.mp');
    


    // Financeiro - Pagar
    Route::resource('payables', PayableController::class)->except(['destroy']);
    Route::post('payables/{payable}/pay', [PayableController::class, 'pay'])->name('payables.pay');
    Route::post('payables/{payable}/reverse', [PayableController::class, 'reverse'])->name('payables.reverse');
    Route::post('payables/{payable}/cancel', [PayableController::class, 'cancel'])->name('payables.cancel');

    // Ordens de Serviço
    Route::resource('service_orders', ServiceOrderController::class);
    Route::get('service_orders/{service_order}/finalize', [ServiceOrderController::class, 'finalizeForm'])->name('service_orders.finalize_form');
    Route::post('service_orders/{service_order}/finalize', [ServiceOrderController::class, 'finalize'])->name('service_orders.finalize');
    Route::get('service_orders/{service_order}/delivery-receipt', [ServiceOrderController::class, 'deliveryReceipt'])->name('service_orders.delivery_receipt');
    Route::get('service_orders/{service_order}/warranty-receipt', [ServiceOrderController::class, 'warrantyReceipt'])->name('service_orders.warranty_receipt');
    Route::post('service_orders/{service_order}/revert-warranty', [ServiceOrderController::class, 'revertWarranty'])->name('service_orders.revert_warranty');
    Route::get('service_orders/{service_order}/warranty-cancellation-receipt', [ServiceOrderController::class, 'warrantyCancellationReceipt'])->name('service_orders.warranty_cancellation_receipt');
    Route::post('service_orders/{service_order}/items', [ServiceOrderController::class, 'addItem'])->name('service_orders.add_item');
    Route::delete('service_orders/{service_order}/items/{item}', [ServiceOrderController::class, 'removeItem'])->name('service_orders.remove_item');
    Route::post('service_orders/{service_order}/attachments', [ServiceOrderController::class, 'addAttachment'])->name('service_orders.add_attachment');
    Route::delete('service_orders/{service_order}/attachments/{attachment}', [ServiceOrderController::class, 'removeAttachment'])->name('service_orders.remove_attachment');
    Route::get('service_orders/{service_order}/print', [ServiceOrderController::class, 'print'])->name('service_orders.print');
    Route::post('service_orders/{service_order}/finalize', [ServiceOrderController::class, 'finalize'])->name('service_orders.finalize');
    Route::post('service_orders/{service_order}/approve', [ServiceOrderController::class, 'approve'])->name('service_orders.approve');
    Route::post('service_orders/{service_order}/notify', [ServiceOrderController::class, 'notify'])->name('service_orders.notify');
    Route::post('service_orders/{service_order}/reject', [ServiceOrderController::class, 'reject'])->name('service_orders.reject');
    Route::post('service_orders/{service_order}/issue-nfe', [ServiceOrderController::class, 'issueNfe'])->name('service_orders.issue_nfe');
    Route::post('service_orders/{service_order}/issue-nfse', [ServiceOrderController::class, 'issueNfse'])->name('service_orders.issue_nfse');

    // E-mails de OS (aprovação / retirada)
    Route::get('service_orders/{service_order}/email', [ServiceOrderController::class, 'emailForm'])->name('service_orders.email_form');
    Route::post('service_orders/{service_order}/email', [ServiceOrderController::class, 'sendEmail'])->name('service_orders.email_send');
    
    // Ocorrências de OS
    Route::post('service_orders/{service_order}/occurrences', [ServiceOrderController::class, 'addOccurrence'])->name('service_orders.add_occurrence');
    Route::get('service_orders/{service_order}/occurrences', [ServiceOrderController::class, 'getOccurrences'])->name('service_orders.get_occurrences');
    Route::get('service_orders/{service_order}/finalize', [ServiceOrderController::class, 'finalizeForm'])->name('service_orders.finalize_form');
    Route::post('service_orders/{service_order}/finalize', [ServiceOrderController::class, 'finalize'])->name('service_orders.finalize');
    Route::get('service_orders/{service_order}/delivery-receipt', [ServiceOrderController::class, 'deliveryReceipt'])->name('service_orders.delivery_receipt');
    
// Cancelamento de OS
Route::get('service_orders/{service_order}/cancel', [ServiceOrderController::class, 'cancelForm'])->name('service_orders.cancel_form');
Route::post('service_orders/{service_order}/cancel', [ServiceOrderController::class, 'cancel'])->name('service_orders.cancel');
Route::get('service_orders/{service_order}/cancellation-impacts', [ServiceOrderController::class, 'getCancellationImpacts'])->name('service_orders.cancellation_impacts');
Route::get('service_orders/{service_order}/cancellation-receipt', [ServiceOrderController::class, 'cancellationReceipt'])->name('service_orders.cancellation_receipt');

// Rotas de Garantia
Route::post('service_orders/{service_order}/create-warranty', [ServiceOrderController::class, 'createWarranty'])->name('service_orders.create_warranty');
Route::post('service_orders/{service_order}/mark-not-warranty', [ServiceOrderController::class, 'markAsNotWarranty'])->name('service_orders.mark_not_warranty');
Route::post('service_orders/{service_order}/extend-warranty', [ServiceOrderController::class, 'extendWarranty'])->name('service_orders.extend_warranty');

    // Caixa do Dia
    Route::get('/cash', [DailyCashController::class, 'show'])->name('cash.show');
    Route::post('/cash/close', [DailyCashController::class, 'close'])->name('cash.close');

    // Sangria de Caixa (CRUD)
    Route::resource('cash_withdrawals', \App\Http\Controllers\CashWithdrawalController::class)->except(['show']);

    // Orçamentos
    Route::resource('quotes', QuoteController::class);
    Route::post('quotes/{quote}/approve', [QuoteController::class, 'approve'])->name('quotes.approve');
    Route::post('quotes/{quote}/notify', [QuoteController::class, 'notify'])->name('quotes.notify');
    Route::post('quotes/{quote}/reject', [QuoteController::class, 'reject'])->name('quotes.reject');
    Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
    Route::get('quotes/{quote}/print', [QuoteController::class, 'print'])->name('quotes.print');
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'generatePdf'])->name('quotes.pdf');
    Route::get('quotes/{quote}/whatsapp', [QuoteController::class, 'whatsapp'])->name('quotes.whatsapp');
    Route::get('quotes/{quote}/audit', [QuoteController::class, 'audit'])->name('quotes.audit');
    // E-mail de Orçamento (sem aprovação, apenas envio)
    Route::get('quotes/{quote}/email', [QuoteController::class, 'emailForm'])->name('quotes.email_form');
    Route::post('quotes/{quote}/email', [QuoteController::class, 'sendEmail'])->name('quotes.email_send');

    // Pedidos
    Route::resource('orders', OrderController::class);
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::get('orders/{order}/pdf', [OrderController::class, 'pdf'])->name('orders.pdf');
    Route::get('orders/{order}/payment', [OrderController::class, 'paymentForm'])->name('orders.payment');
    Route::post('orders/{order}/items', [OrderController::class, 'addItem'])->name('orders.add_item');
    Route::delete('orders/{order}/items/{item}', [OrderController::class, 'removeItem'])->name('orders.remove_item');
    Route::post('orders/{order}/fulfill', [OrderController::class, 'fulfill'])->name('orders.fulfill');
    Route::post('orders/{order}/reopen', [OrderController::class, 'reopen'])->name('orders.reopen');
    Route::get('orders/{order}/prepare-reopen-adjustment', [OrderController::class, 'prepareReopenAdjustment'])->name('orders.prepare_reopen_adjustment');
    Route::post('orders/{order}/reopen-with-adjustment', [OrderController::class, 'reopenWithAdjustment'])->name('orders.reopen.with_adjustment');
    Route::post('orders/{order}/adjust-with-returns', [OrderController::class, 'adjustWithReturns'])->name('orders.adjust_with_returns');
    Route::post('orders/{order}/issue-nfe', [OrderController::class, 'issueNfe'])->name('orders.issue_nfe');
    Route::get('orders/{order}/whatsapp', [OrderController::class, 'whatsapp'])->name('orders.whatsapp');
    // E-mail do Pedido
    Route::get('orders/{order}/email', [OrderController::class, 'emailForm'])->name('orders.email_form');
    Route::post('orders/{order}/email', [OrderController::class, 'sendEmail'])->name('orders.email_send');

    // Restrições por plano (exemplos): emissão de NFe e PDV
    Route::post('orders/{order}/issue-nfe', [OrderController::class, 'issueNfe'])
        ->middleware([\App\Http\Middleware\PlanFeatureMiddleware::class.':allow_issue_nfe'])
        ->name('orders.issue_nfe');
    // NFC-e (PDV)
    Route::post('orders/{order}/issue-nfce', [OrderController::class, 'issueNfce'])
        ->middleware([\App\Http\Middleware\PlanFeatureMiddleware::class.':allow_pos'])
        ->name('orders.issue_nfce');
    // Descontos do pedido (itens e total)
    Route::post('orders/{order}/discounts', [OrderController::class, 'updateDiscounts'])->name('orders.update_discounts');
    Route::get('orders/{order}/audit', [OrderController::class, 'audit'])->name('orders.audit');
    Route::get('/pos', [POSController::class, 'index'])
        ->middleware([\App\Http\Middleware\PlanFeatureMiddleware::class.':allow_pos'])
        ->name('pos.index');

    // Notas de Saída (NFe Management)
    Route::get('nfe', [\App\Http\Controllers\NfeManagementController::class, 'index'])->name('nfe.index');
    Route::post('nfe/{nfe}/cancel', [\App\Http\Controllers\NfeManagementController::class, 'cancel'])->name('nfe.cancel');
    Route::post('nfe/{nfe}/cce', [\App\Http\Controllers\NfeManagementController::class, 'cce'])->name('nfe.cce');
    Route::post('nfe/inutilizar', [\App\Http\Controllers\NfeManagementController::class, 'inutilizar'])->name('nfe.inutilizar');
    Route::post('nfe/inutilizacao/reprocessar', [\App\Http\Controllers\NfeManagementController::class, 'reprocessInutilizacoes'])->name('nfe.inut.reprocess');

    // Transportadoras (Frete)
    Route::resource('carriers', \App\Http\Controllers\CarrierController::class)->except(['show']);

    // Fornecedores
    Route::resource('suppliers', SupplierController::class)->except(['show']);

    // Categorias
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Regras NCM → GTIN
    Route::resource('ncm_rules', NcmRuleController::class)->except(['show'])->parameters(['ncm_rules' => 'ncm_rule']);

    // Notas de Entrada (NFe XML)
    Route::get('/inbound', [InboundInvoiceController::class, 'index'])->name('inbound.index');
    Route::get('/inbound/create', [InboundInvoiceController::class, 'create'])->name('inbound.create');
    Route::post('/inbound', [InboundInvoiceController::class, 'store'])->name('inbound.store');
    Route::get('/inbound/{inbound}/edit', [InboundInvoiceController::class, 'edit'])->name('inbound.edit');
    Route::put('/inbound/{inbound}', [InboundInvoiceController::class, 'update'])->name('inbound.update');
    Route::post('/inbound/{inbound}/items/{item}/unlink', [InboundInvoiceController::class, 'unlinkItem'])->name('inbound.items.unlink');

    // PDV (POS)
    Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
    Route::post('/pos', [POSController::class, 'store'])->name('pos.store');
    Route::get('/pos/sales', [POSController::class, 'sales'])->name('pos.sales');
    Route::get('/pos/{order}/receipt', [POSController::class, 'receipt'])->name('pos.receipt');
    Route::get('/pos/{order}/print', [POSController::class, 'printOrder'])->name('pos.print');
    Route::get('/pos/{order}/print-80', [POSController::class, 'printOrder80'])->name('pos.print80');

    // Configurações gerais
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Configurações Fiscais (página dedicada)
    Route::get('/settings/fiscal', [SettingsController::class, 'editFiscal'])->name('settings.fiscal.edit');
    Route::put('/settings/fiscal', [SettingsController::class, 'updateFiscal'])->name('settings.fiscal.update');

    // Atividades do Sistema
    Route::get('/activity', [App\Http\Controllers\ActivityController::class, 'index'])->name('activity.index');

    // Tributações (CRUD)
    Route::resource('tax_rates', TaxRateController::class)->parameters(['tax_rates' => 'tax_rate'])->except(['show']);
    Route::get('tax_rates/{tax_rate}', [TaxRateController::class, 'show'])->name('tax_rates.show');
    Route::get('tax_rates/{tax_rate}/history', [TaxRateController::class, 'history'])->name('tax_rates.history');
    Route::resource('tax_credits', TaxCreditController::class)->parameters(['tax_credits' => 'tax_credit']);
    Route::get('tax_credits/{product_id}/suggestion', [TaxCreditController::class, 'getIcmsSuggestion'])->name('tax_credits.suggestion');
    Route::get('tax_credits/{product_id}/available', [TaxCreditController::class, 'getAvailableCredits'])->name('tax_credits.available');
    Route::get('/tax_rates/{tax_rate}/json', [TaxRateController::class, 'showJson'])->name('tax_rates.json');

    // Devoluções
    Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('/returns/select', [ReturnController::class, 'selectOrder'])->name('returns.select');
    Route::get('/returns/create', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');

    // Relatórios
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/print', [ReportsController::class, 'print'])->name('reports.print');

    // Recibos
    Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/edit', [ReceiptController::class, 'edit'])->name('receipts.edit');
    Route::put('/receipts/{receipt}', [ReceiptController::class, 'update'])->name('receipts.update');
    Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
    Route::get('/receipts/{receipt}/print', [ReceiptController::class, 'print'])->name('receipts.print');
    Route::get('/receipts/{receipt}/email', [ReceiptController::class, 'emailForm'])->name('receipts.email_form');
    Route::post('/receipts/{receipt}/email', [ReceiptController::class, 'sendEmail'])->name('receipts.email_send');

    // Calendário
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');
    Route::delete('/calendar/events/{event}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');

    // Web APIs (sem Sanctum) para uso nos formulários
    Route::get('/webapi/categories/{category}/default-cfop', [CategoryController::class, 'defaultCfop'])->name('webapi.categories.default_cfop');
    Route::get('/webapi/ncm/{ncm}/requires-gtin', function (string $ncm) {
        $rule = \App\Models\NcmRule::where('ncm', $ncm)->first();
        return response()->json([
            'ncm' => $ncm,
            'requires_gtin' => (bool)($rule->requires_gtin ?? false),
            'note' => $rule->note ?? null,
        ]);
    })->name('webapi.ncm.requires_gtin');
});

// Rota para admin acessar partner/tenants (deve vir antes da rota com auth:partner)
Route::middleware(['web','auth', \App\Http\Middleware\AdminMiddleware::class])->group(function(){
    Route::get('/partner/tenants', [App\Http\Controllers\Partner\TenantsController::class, 'index'])->name('admin.partner.tenants');
});

// Painel do Parceiro
Route::middleware(['web','auth:partner'])->group(function(){
    Route::get('/partner', [App\Http\Controllers\PartnerDashboardController::class, 'index'])->name('partner.dashboard');
    Route::get('/partner/invite-client', [App\Http\Controllers\PartnerDashboardController::class, 'inviteClient'])->name('partner.invite-client');
    Route::post('/partner/generate-invite-link', [App\Http\Controllers\PartnerDashboardController::class, 'generateInviteLink'])->name('partner.generate-invite-link');
    Route::prefix('partner')->name('partner.')->group(function(){
        Route::get('tenants', [App\Http\Controllers\Partner\TenantsController::class, 'index'])->name('tenants.index');
        Route::get('invoices', [App\Http\Controllers\Partner\InvoicesController::class, 'index'])->name('invoices.index');
        Route::get('payments', [App\Http\Controllers\Partner\PaymentsController::class, 'index'])->name('payments.index');
        Route::get('storage-usage', [App\Http\Controllers\PartnerDashboardController::class, 'storageUsage'])->name('storage-usage');
    });
});

// Rotas públicas de aprovação por link
Route::get('/so/{service_order}/approval', [ServiceOrderController::class, 'publicApproval'])
    ->name('service_orders.public_approve');

// Rotas administrativas
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('dashboard');
    Route::match(['GET','POST'], '/emitter/health', [App\Http\Controllers\Admin\AdminController::class, 'emitterHealthcheck'])->name('emitter-healthcheck');
    Route::get('/emitter/health/json', [App\Http\Controllers\Admin\AdminController::class, 'emitterHealthcheckJson'])->name('emitter-healthcheck-json');
    Route::get('/delphi-config', [App\Http\Controllers\Admin\AdminController::class, 'delphiConfig'])->name('delphi-config');
    Route::put('/delphi-config', [App\Http\Controllers\Admin\AdminController::class, 'updateDelphiConfig'])->name('delphi-config.update');
    Route::get('/smtp', [App\Http\Controllers\Admin\AdminController::class, 'smtpSettings'])->name('smtp-settings');
    Route::post('/smtp', [App\Http\Controllers\Admin\AdminController::class, 'updateSmtpSettings'])->name('smtp-settings.update');
    Route::get('/tenants', [App\Http\Controllers\Admin\AdminController::class, 'tenants'])->name('tenants');
    Route::get('/tenants/{tenant}/edit', [App\Http\Controllers\Admin\AdminController::class, 'editTenant'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [App\Http\Controllers\Admin\AdminController::class, 'updateTenant'])->name('tenants.update');
    Route::post('/tenants/{tenant}/toggle', [App\Http\Controllers\Admin\AdminController::class, 'toggleTenantStatus'])->name('tenants.toggle');
    Route::get('/payments', [App\Http\Controllers\Admin\AdminController::class, 'payments'])->name('payments');
    Route::get('/storage-usage', [App\Http\Controllers\Admin\AdminController::class, 'storageUsage'])->name('storage-usage');
    
            // Rotas de Planos
        Route::get('/plans', [App\Http\Controllers\Admin\AdminController::class, 'plans'])->name('plans');
        Route::get('/plans/create', [App\Http\Controllers\Admin\AdminController::class, 'createPlan'])->name('plans.create');
        Route::post('/plans', [App\Http\Controllers\Admin\AdminController::class, 'storePlan'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [App\Http\Controllers\Admin\AdminController::class, 'editPlan'])->name('plans.edit');
        Route::put('/plans/{plan}', [App\Http\Controllers\Admin\AdminController::class, 'updatePlan'])->name('plans.update');
        Route::post('/plans/{plan}/toggle', [App\Http\Controllers\Admin\AdminController::class, 'togglePlanStatus'])->name('plans.toggle');

    // Gateway Mercado Pago
    Route::get('/gateway', [App\Http\Controllers\Admin\GatewayController::class, 'edit'])->name('gateway.edit');
    Route::put('/gateway', [App\Http\Controllers\Admin\GatewayController::class, 'update'])->name('gateway.update');

    // Novidades
    Route::get('/news', [App\Http\Controllers\Admin\NewsController::class, 'index'])->name('news.index');
    Route::get('/news/create', [App\Http\Controllers\Admin\NewsController::class, 'create'])->name('news.create');
    Route::post('/news', [App\Http\Controllers\Admin\NewsController::class, 'store'])->name('news.store');
    Route::delete('/news/bulk-delete', [App\Http\Controllers\Admin\NewsController::class, 'bulkDelete'])->name('news.bulk-delete');
    Route::get('/news/{news}/edit', [App\Http\Controllers\Admin\NewsController::class, 'edit'])->name('news.edit');
    Route::put('/news/{news}', [App\Http\Controllers\Admin\NewsController::class, 'update'])->name('news.update');
    Route::delete('/news/{news}', [App\Http\Controllers\Admin\NewsController::class, 'destroy'])->name('news.destroy');

    // Teste de E-mail
    Route::get('/email-test', [App\Http\Controllers\Admin\EmailTestController::class, 'index'])->name('email-test.index');
    Route::post('/email-test', [App\Http\Controllers\Admin\EmailTestController::class, 'send'])->name('email-test.send');

    // Parceiros (white-label)
    Route::resource('partners', App\Http\Controllers\Admin\PartnerController::class);
    Route::post('partners/{partner}/invite', [App\Http\Controllers\Admin\PartnerController::class, 'sendInvite'])->name('partners.invite');
    Route::post('partners/{partner}/credentials', [App\Http\Controllers\Admin\PartnerController::class, 'sendCredentials'])->name('partners.credentials');

});

// Rotas de Redefinição de Senha (fora do middleware de auth)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/reset-password', [App\Http\Controllers\Admin\ResetPasswordController::class, 'showResetForm'])->name('reset-password');
    Route::post('/reset-password', [App\Http\Controllers\Admin\ResetPasswordController::class, 'resetPassword'])->name('reset-password.post');
});

require __DIR__.'/auth.php';

// Webhooks (público)
Route::post('/webhooks/mercadopago', [\App\Http\Controllers\Webhooks\MercadoPagoWebhookController::class, 'handle'])->name('webhooks.mercadopago');