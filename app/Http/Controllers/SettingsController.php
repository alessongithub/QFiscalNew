<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        // Permitir acesso se usuário puder editar todas as configurações OU apenas as fiscais
        $user = auth()->user();
        abort_unless($user->hasPermission('settings.edit') || $user->hasPermission('tax_config.edit'), 403);
        $defaults = [
            'ui.theme' => 'light', // light|dark
            'print.default' => 'a4', // a4|80mm
            'print.footer' => '',
            'pos.require_client' => '0',
            'pos.block_without_stock' => '1',
            'stock.allow_negative' => '0',
            'pos.default_cash_method' => 'cash',
            'pos.default_installment_method' => 'boleto',
            'service_orders.default_warranty_days' => '90',
            'boleto.fine_percent' => '0',
            'boleto.interest_month_percent' => '0',
            'orders.cancel.max_days' => '90',
            'orders.cancel.card_anticipation_fee_percent' => '3.5',
            'whatsapp.order_template' => 'Olá {cliente}, seu pedido #{numero} - {titulo} no valor de R$ {total} está {status}. Itens:\n{itens}',
            'whatsapp.quote_template' => 'Olá {cliente}, seu orçamento #{numero} - {titulo} no valor de R$ {total} está {status}. Itens:\n{itens}',
        ];
        $values = [];
        foreach ($defaults as $k=>$v) { $values[$k] = Setting::get($k, $v); }

        // Carrega/instancia config fiscal do tenant
        $tenant = $user->tenant;
        $taxConfig = \App\Models\TenantTaxConfig::firstOrCreate(
            ['tenant_id' => $tenant->id],
            []
        );

        // Carregar emissor do tenant
        $emitter = \App\Models\TenantEmitter::firstOrCreate(['tenant_id' => $tenant->id], []);

        return view('settings.edit', compact('values','taxConfig','user','emitter'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('settings.edit') || $user->hasPermission('tax_config.edit'), 403);
        $flat = [
            'ui.theme' => $request->input('ui.theme', $request->input('ui.theme', 'light')),
            'print.default' => $request->input('print.default', 'a4'),
            'print.footer' => $request->input('print.footer', ''),
            'pos.require_client' => $request->input('pos.require_client', '0'),
            'pos.block_without_stock' => $request->input('pos.block_without_stock', '1'),
            'stock.allow_negative' => $request->input('stock.allow_negative', '0'),
            'pos.default_cash_method' => $request->input('pos.default_cash_method', 'cash'),
            'pos.default_installment_method' => $request->input('pos.default_installment_method', 'boleto'),
            'service_orders.default_warranty_days' => $request->input('service_orders.default_warranty_days', '90'),
            'orders.cancel.max_days' => $request->input('orders.cancel.max_days', '90'),
            'orders.cancel.card_anticipation_fee_percent' => $request->input('orders.cancel.card_anticipation_fee_percent', '3.5'),
        ];
        // Normaliza quando enviados como arrays (ui[theme], etc.)
        $groups = $request->only(['ui','print','pos','stock','service_orders','boleto','whatsapp','nfe','orders']);
        if (isset($groups['ui']['theme'])) { $flat['ui.theme'] = $groups['ui']['theme']; }
        if (isset($groups['print']['default'])) { $flat['print.default'] = $groups['print']['default']; }
        if (isset($groups['print']['footer'])) { $flat['print.footer'] = $groups['print']['footer']; }
        if (isset($groups['pos']['require_client'])) { $flat['pos.require_client'] = $groups['pos']['require_client']; }
        if (isset($groups['pos']['block_without_stock'])) { $flat['pos.block_without_stock'] = $groups['pos']['block_without_stock']; }
        if (isset($groups['stock']['allow_negative'])) { $flat['stock.allow_negative'] = $groups['stock']['allow_negative']; }
        if (isset($groups['service_orders']['default_warranty_days'])) { $flat['service_orders.default_warranty_days'] = $groups['service_orders']['default_warranty_days']; }
        if (isset($groups['pos']['default_cash_method'])) { $flat['pos.default_cash_method'] = $groups['pos']['default_cash_method']; }
        if (isset($groups['pos']['default_installment_method'])) { $flat['pos.default_installment_method'] = $groups['pos']['default_installment_method']; }
        if (isset($groups['orders']['cancel']['max_days'])) { $flat['orders.cancel.max_days'] = $groups['orders']['cancel']['max_days']; }
        if (isset($groups['orders']['cancel']['card_anticipation_fee_percent'])) { $flat['orders.cancel.card_anticipation_fee_percent'] = $groups['orders']['cancel']['card_anticipation_fee_percent']; }

        // Valida usando chaves aninhadas
        // Validação condicional por permissão
        if ($user->hasPermission('settings.edit')) {
            $request->validate([
                'ui.theme' => 'required|in:light,dark',
                'print.default' => 'required|in:a4,80mm',
                'print.footer' => 'nullable|string|max:200',
                'pos.require_client' => 'required|in:0,1',
                'pos.block_without_stock' => 'required|in:0,1',
                'stock.allow_negative' => 'required|in:0,1',
                'pos.default_cash_method' => 'required|in:cash,card,pix',
                'pos.default_installment_method' => 'required|in:boleto,card',
                'service_orders.default_warranty_days' => 'required|integer|min:0|max:3650',
                'orders.cancel.max_days' => 'nullable|integer|min:0|max:365',
                'orders.cancel.card_anticipation_fee_percent' => 'nullable|numeric|min:0|max:100',
            ]);
        }

        // Validação da seção fiscal
        if ($user->hasPermission('tax_config.edit')) {
            $request->validate([
                'tax.regime_tributario' => 'required|in:simples_nacional,lucro_presumido,lucro_real',
                'tax.cnae_principal' => 'nullable|string|max:20',
                'tax.anexo_simples' => 'nullable|in:I,II,III,IV,V',
                'tax.aliquota_simples_nacional' => 'nullable|numeric|min:0|max:1',
                'tax.habilitar_ibpt' => 'nullable|in:0,1',
                'tax.codigo_ibpt_padrao' => 'nullable|string|max:50',
            ]);
        }

        // Validação NFe: série e próximo número (por tenant)
        if ($user->hasPermission('settings.edit')) {
            $request->validate([
                'nfe.series' => 'nullable|integer|min:1|max:999',
                'nfe.next_number' => 'nullable|integer|min:1|max:999999999',
                'nfe.environment' => 'nullable|in:homologacao,producao',
                'nfe.certificate_serial' => 'nullable|string|max:255',
            ]);
        }

        // Persistir grupos diretamente (mais resiliente)
        if ($user->hasPermission('settings.edit')) {
            foreach ((array)$request->input('ui', []) as $k=>$v) { Setting::set('ui.'.$k, (string)$v); }
            foreach ((array)$request->input('print', []) as $k=>$v) { Setting::set('print.'.$k, (string)$v); }
            foreach ((array)$request->input('pos', []) as $k=>$v) { Setting::set('pos.'.$k, (string)$v); }
            foreach ((array)$request->input('stock', []) as $k=>$v) { Setting::set('stock.'.$k, (string)$v); }
            foreach ((array)$request->input('service_orders', []) as $k=>$v) { Setting::set('service_orders.'.$k, (string)$v); }
            foreach ((array)$request->input('boleto', []) as $k=>$v) { Setting::set('boleto.'.$k, (string)$v); }
            foreach ((array)$request->input('whatsapp', []) as $k=>$v) { Setting::set('whatsapp.'.$k, (string)$v); }
            // NFe (ambiente por tenant)
            $nfeEnv = (string) ($request->input('nfe.environment', ''));
            if (in_array($nfeEnv, ['homologacao','producao'], true)) {
                Setting::set('nfe.environment', $nfeEnv);
            }

            // NFe: certificado instalado (número de série)
            $certSerial = (string) ($request->input('nfe.certificate_serial', ''));
            if ($certSerial !== '') {
                Setting::set('nfe.certificate_serial', $certSerial);
            }

            // NFe: série e próximo número sequencial (inicial)
            $serie = $request->input('nfe.series');
            if ($serie !== null && $serie !== '') {
                $serie = (string) ((int) $serie);
                Setting::set('nfe.series', $serie);
                $next = $request->input('nfe.next_number');
                if ($next !== null && (int)$next > 0) {
                    Setting::set('nfe.next_number.series.' . $serie, (string) ((int) $next));
                }
            }
        }

        // Persistir configuração fiscal
        if ($user->hasPermission('tax_config.edit')) {
            $tenant = $user->tenant;
            $tax = (array)$request->input('tax', []);
            $config = \App\Models\TenantTaxConfig::firstOrCreate(['tenant_id' => $tenant->id]);
            $config->regime_tributario = $tax['regime_tributario'] ?? $config->regime_tributario ?? 'simples_nacional';
            $config->cnae_principal = $tax['cnae_principal'] ?? null;
            $config->anexo_simples = $tax['anexo_simples'] ?? null;
            $config->aliquota_simples_nacional = $tax['aliquota_simples_nacional'] ?? null;
            $config->habilitar_ibpt = (isset($tax['habilitar_ibpt']) ? (bool)$tax['habilitar_ibpt'] : false);
            $config->codigo_ibpt_padrao = $tax['codigo_ibpt_padrao'] ?? null;
            $config->save();
        }


        // Persistir emissor
        if ($user->hasPermission('tax_config.edit')) {
            $tenant = $user->tenant;
            $emInput = (array) $request->input('emitter', []);

            // Validações básicas
            $request->validate([
                'emitter.cnpj' => 'nullable|string|max:18',
                'emitter.ie' => 'nullable|string|max:20',
                'emitter.razao_social' => 'nullable|string|max:255',
                'emitter.nome_fantasia' => 'nullable|string|max:255',
                'emitter.email' => 'nullable|email|max:255',
                'emitter.phone' => 'nullable|string|max:20',
                'emitter.zip_code' => 'nullable|string|max:10',
                'emitter.address' => 'nullable|string|max:255',
                'emitter.number' => 'nullable|string|max:20',
                'emitter.complement' => 'nullable|string|max:100',
                'emitter.neighborhood' => 'nullable|string|max:100',
                'emitter.city' => 'nullable|string|max:100',
                'emitter.state' => 'nullable|string|max:2',
                'emitter.codigo_ibge' => 'nullable|string|max:7',
                'emitter.nfe_model' => 'nullable|in:55',
                'emitter.nfe_serie' => 'nullable|string|max:3',
                'emitter.nfe_number_current' => 'nullable|integer|min:0',
                'emitter.icms_credit_percent' => 'nullable|numeric|min:0|max:100',
                'emitter.base_storage_disk' => 'nullable|in:local,s3',
                'emitter.base_storage_path' => 'nullable|string|max:255',
                'emitter.certificate_password' => 'nullable|string|max:255',
                'emitter.certificate_valid_until' => 'nullable|date',
            ]);

            $emitter = \App\Models\TenantEmitter::firstOrCreate(['tenant_id' => $tenant->id]);

            // Upload do certificado PFX (opcional)
            if ($request->hasFile('emitter.certificate_file')) {
                $file = $request->file('emitter.certificate_file');
                if ($file->isValid()) {
                    $disk = $emitter->base_storage_disk ?: config('filesystems.default', 'local');
                    $basePath = $emitter->base_storage_path ?: ('tenants/'.$tenant->id.'/nfe/cert');
                    $path = $file->store($basePath, $disk);
                    $emInput['certificate_path'] = $path;
                }
            }

            // Criptografar senha se enviada
            if (!empty($emInput['certificate_password'])) {
                $emInput['certificate_password_encrypted'] = encrypt($emInput['certificate_password']);
                unset($emInput['certificate_password']);
            }

            $emitter->fill($emInput);
            $emitter->tenant_id = $tenant->id;
            $emitter->save();

            // Garantir diretórios
            try { $emitter->ensureDirectories(); } catch (\Throwable $e) { /* ignore */ }
        }

        return back()->with('success','Configurações salvas.');
        // Persistência de PFX e senha do emissor (se enviados)
        try {
            $tenantId = auth()->user()->tenant_id;
            $emitter = \App\Models\TenantEmitter::firstOrCreate(['tenant_id' => $tenantId], []);
            // Aceita dois nomes de campo para compatibilidade
            if ($request->hasFile('nfe_pfx') || $request->hasFile('nfe.pfx')) {
                $file = $request->file('nfe_pfx') ?: $request->file('nfe.pfx');
                if ($file && $file->isValid()) {
                    $path = $file->store('tenants/' . $tenantId . '/nfe/cert');
                    $emitter->certificado_caminho = $path;
                }
            }
            $pwd = (string) ($request->input('nfe_pfx_password') ?? $request->input('nfe.pfx_password') ?? '');
            if ($pwd !== '') {
                $emitter->certificado_senha = encrypt($pwd);
            }
            if ($emitter->isDirty()) { $emitter->save(); }
        } catch (\Throwable $e) {
            // Não interrompe outras configurações; apenas registra
            \Log::warning('Falha ao salvar PFX do emissor', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Configurações salvas.');
    }

    public function updateCertSerial(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $serial = trim((string) $request->input('serial', ''));
        if ($serial === '') {
            return response()->json(['error' => 'Serial inválido'], 422);
        }
        \App\Models\Setting::set('nfe.certificate_serial', $serial);
        return response()->json(['ok' => true, 'serial' => $serial]);
    }
}


