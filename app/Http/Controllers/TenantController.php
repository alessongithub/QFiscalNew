<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\SmtpConfig;
use App\Http\Controllers\Admin\EmailTestController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    // Primeira etapa - dados básicos do usuário
    public function create()
    {
        // Verificar se há token de convite do parceiro
        $partnerToken = request()->get('token');
        $partnerInvite = null;
        
        if ($partnerToken) {
            $partnerInvite = \Illuminate\Support\Facades\Cache::get("partner_invite_{$partnerToken}");
        }
        
        // Capturar plano selecionado da landing page
        $planoSelecionado = request()->get('plano', 'gratuito');
        
        // Se houver invite do parceiro, armazenar na sessão
        if ($partnerInvite) {
            \Illuminate\Support\Facades\Session::put('partner_invite_token', $partnerToken);
            \Illuminate\Support\Facades\Session::put('partner_invite_data', $partnerInvite);
        }
        
        return view('tenants.register-step1', compact('planoSelecionado', 'partnerInvite'));
    }

    public function storeStep1(Request $request)
    {
        $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'admin_name.required' => 'O nome é obrigatório.',
            'admin_email.required' => 'O email é obrigatório.',
            'admin_email.email' => 'Digite um email válido.',
            'admin_email.unique' => 'Este email já está sendo usado.',
            'admin_password.required' => 'A senha é obrigatória.',
            'admin_password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'admin_password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        // Armazenar dados na sessão
        Session::put('registration_step1', [
            'admin_name' => $request->admin_name,
            'admin_email' => $request->admin_email,
            'admin_password' => $request->admin_password,
            'plano_selecionado' => $request->plano_selecionado ?? 'gratuito',
        ]);

        return redirect()->route('tenant.register.step2');
    }

    // Segunda etapa - dados da empresa
    public function createStep2()
    {
        // Verificar se a primeira etapa foi concluída
        if (!Session::has('registration_step1')) {
            return redirect()->route('tenant.register')->with('error', 'Complete primeiro os dados básicos.');
        }

        return view('tenants.register-step2');
    }

    public function storeStep2(Request $request)
    {
        // Debug temporário
        \Log::info('Iniciando storeStep2', $request->all());
        
        // Verificar se a primeira etapa foi concluída
        if (!Session::has('registration_step1')) {
            \Log::error('Sessão step1 não encontrada');
            return redirect()->route('tenant.register')->with('error', 'Complete primeiro os dados básicos.');
        }
        
        \Log::info('Sessão step1 encontrada', Session::get('registration_step1'));

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fantasy_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'min:14', 'max:18', 'unique:tenants,cnpj'], // Formato: 00.000.000/0000-00
            'phone' => ['required', 'string', 'min:14', 'max:20'], // Formato: (00) 00000-0000
            'address' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'min:8', 'max:9'], // Formato: 00000-000
        ], [
            'name.required' => 'A razão social é obrigatória.',
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.min' => 'Digite um CNPJ válido.',
            'cnpj.max' => 'Digite um CNPJ válido.',
            'phone.required' => 'O telefone é obrigatório.',
            'phone.min' => 'Digite um telefone válido.',
            'address.required' => 'O endereço é obrigatório.',
            'number.required' => 'O número é obrigatório.',
            'neighborhood.required' => 'O bairro é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',
            'zip_code.required' => 'O CEP é obrigatório.',
            'zip_code.min' => 'Digite um CEP válido.',
        ]);

        try {
            DB::beginTransaction();

            // Recuperar dados da primeira etapa
            $step1Data = Session::get('registration_step1');

            // Determinar qual plano usar baseado na seleção (usando slugs do seeder: free, basic, professional, enterprise)
            $planoSelecionado = $step1Data['plano_selecionado'] ?? 'gratuito';
            $map = [
                'gratuito' => 'free',
                'basico' => 'basic',
                'profissional' => 'professional',
                'enterprise' => 'enterprise',
            ];
            $planSlug = $map[$planoSelecionado] ?? 'free';

            \Log::info('Plano selecionado na landing: ' . $planoSelecionado);
            \Log::info('Plano mapeado para: ' . $planSlug);

            $plan = Plan::where('slug', $planSlug)->where('active', true)->firstOrFail();

            // Verificar se há convite do parceiro na sessão
            $partnerToken = Session::get('partner_invite_token');
            $partnerInvite = null;
            $partnerId = null;
            
            if ($partnerToken) {
                $partnerInvite = \Illuminate\Support\Facades\Cache::get("partner_invite_{$partnerToken}");
                if ($partnerInvite && isset($partnerInvite['partner_id'])) {
                    $partnerId = $partnerInvite['partner_id'];
                }
                // Limpar o token após uso
                \Illuminate\Support\Facades\Cache::forget("partner_invite_{$partnerToken}");
                Session::forget('partner_invite_token');
                Session::forget('partner_invite_data');
            }
            
            // Se não tem partner_id do convite, tentar detectar pelo contexto
            if (!$partnerId) {
                $partnerId = app()->bound('partner') ? optional(app('partner'))->id : null;
            }
            
            // Criar o tenant
            $tenant = Tenant::create([
                'name' => $request->name,
                'fantasy_name' => $request->fantasy_name,
                'email' => $step1Data['admin_email'], // Email do admin como email do tenant
                'cnpj' => preg_replace('/[^0-9]/', '', $request->cnpj), // Remover formatação
                'phone' => preg_replace('/[^0-9]/', '', $request->phone), // Remover formatação
                'address' => $request->address,
                'number' => $request->number,
                'complement' => $request->complement,
                'neighborhood' => $request->neighborhood,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => preg_replace('/[^0-9]/', '', $request->zip_code), // Remover formatação
                'database_name' => 'tenant_' . Str::slug($request->name) . '_' . Str::random(5),
                'status' => 'active',
                'active' => true,
                'plan_id' => $plan->id,
                'plan_expires_at' => $plan->slug === 'free' ? null : now()->addMonth(), // plano gratuito não expira, outros expiram em 1 mês
                'partner_id' => $partnerId,
            ]);

            // Criar usuário admin
            $user = User::create([
                'name' => $step1Data['admin_name'],
                'email' => $step1Data['admin_email'],
                'password' => Hash::make($step1Data['admin_password']),
                'tenant_id' => $tenant->id,
                'is_admin' => false,
            ]);

            // Atribuir papel de administrador do tenant (por slug)
            if ($roleAdmin = Role::where('slug', 'admin')->first()) {
                $user->roles()->syncWithoutDetaching([$roleAdmin->id]);
            }

            // Garantir também o vínculo explícito com role_id = 1, se existir
            if ($roleIdOne = Role::find(1)) {
                $user->roles()->syncWithoutDetaching([$roleIdOne->id]);
            }

            DB::commit();

            // Enviar email de ativação
            $this->enviarEmailAtivacao($user, $tenant);

            // Se plano não for gratuito, redirecionar para checkout
            if ($plan->price > 0) {
                return redirect()->route('checkout.index', ['plan_id' => $plan->id]);
            }

            // Limpar dados da sessão
            Session::forget('registration_step1');

            // Não fazer login automático, apenas redirecionar para a tela de sucesso
            return redirect()->route('tenant.registration.completed');
        } catch (\Exception $e) {
            DB::rollBack();
            // Adicionar debug temporário
            \Log::error('Erro no cadastro: ' . $e->getMessage());
            return back()->with('error', 'Erro ao cadastrar empresa: ' . $e->getMessage());
        }
    }

    /**
     * Enviar email de ativação da conta
     */
    private function enviarEmailAtivacao($user, $tenant)
    {
        try {
            $token = Str::random(64);
            
            // Salvar token na sessão temporariamente (em produção, salvaria no banco)
            Session::put('activation_token_' . $user->id, $token);
            
            // Buscar configuração SMTP ativa
            $active = SmtpConfig::where('is_active', true)->first();
            if (!$active) {
                \Log::error('Configuração SMTP não encontrada para envio de email de ativação');
                return;
            }
            
            $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
            $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
            $username = (string) ($active->username ?? env('MAIL_USERNAME'));
            $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
            $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
            $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
            $fromName = (string) ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name')));
            
            $data = [
                'user' => $user,
                'tenant' => $tenant,
                'activation_url' => url('/activate/' . $user->id . '/' . $token),
                'login_url' => url('/login')
            ];
            
            $html = view('emails.account-activation', $data)->render();
            
            $mailer = new PHPMailer(true);
            EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
            $mailer->addAddress($user->email, $user->name);
            $mailer->isHTML(true);
            $mailer->Subject = 'Ative sua conta - QFiscal';
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($html);
            $mailer->send();

            \Log::info('Email de ativação enviado para: ' . $user->email);
        } catch (PHPMailerException $e) {
            \Log::error('Erro ao enviar email de ativação: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Erro ao enviar email de ativação: ' . $e->getMessage());
        }
    }

    /**
     * Ativar conta do usuário
     */
    public function activateAccount($userId, $token)
    {
        try {
            $user = User::findOrFail($userId);
            
            // Verificar se o token está correto
            $storedToken = Session::get('activation_token_' . $userId);
            
            if (!$storedToken || $storedToken !== $token) {
                return redirect()->route('login')
                    ->with('error', 'Link de ativação inválido ou expirado.');
            }

            // Ativar o usuário
            $user->update([
                'email_verified_at' => now()
            ]);

            // Limpar o token da sessão
            Session::forget('activation_token_' . $userId);

            return redirect()->route('login')
                ->with('status', 'Conta ativada com sucesso! Agora você pode fazer login.');

        } catch (\Exception $e) {
            \Log::error('Erro ao ativar conta: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'Erro ao ativar conta. Tente novamente.');
        }
    }
}