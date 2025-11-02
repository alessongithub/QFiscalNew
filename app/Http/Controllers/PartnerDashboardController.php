<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Partner;
use App\Models\SmtpConfig;
use App\Http\Controllers\Admin\EmailTestController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class PartnerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('partner')->user();
        $partnerId = $user->partner_id;
        $tenantsCount = Tenant::where('partner_id', $partnerId)->count();
        $invoicesCount = Invoice::where('partner_id', $partnerId)->count();
        $paymentsApproved = Payment::where('partner_id', $partnerId)->where('status','approved')->sum('amount');
        $applicationFees = Payment::where('partner_id', $partnerId)->where('status','approved')->sum('application_fee_amount');

        $q = trim((string) $request->get('q', ''));
        $tenants = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->when($q !== '', function($query) use ($q) {
                $query->where(function($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('fantasy_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('cnpj', 'like', "%".preg_replace('/[^0-9]/','',$q)."%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('state', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Próximos vencimentos (com base no tenant.plan_expires_at)
        $upcomingExpirations = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->whereNotNull('plan_expires_at')
            ->orderBy('plan_expires_at')
            ->whereDate('plan_expires_at', '>=', now()->toDateString())
            ->limit(5)
            ->get();

        // Contas em atraso (plan_expires_at no passado)
        $overdueExpirations = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->whereNotNull('plan_expires_at')
            ->whereDate('plan_expires_at', '<', now()->toDateString())
            ->orderBy('plan_expires_at')
            ->limit(5)
            ->get();

        // Últimos pagamentos aprovados
        $recentPayments = Payment::with(['invoice.tenant'])
            ->where('partner_id', $partnerId)
            ->where('status','approved')
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get();

        // Tenants cadastrados hoje
        $todayTenants = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->get();

        $partner = Partner::find($partnerId);
        return view('partner.dashboard', compact('tenantsCount','invoicesCount','paymentsApproved','applicationFees','tenants','q','upcomingExpirations','overdueExpirations','recentPayments','todayTenants','partner'));
    }

    public function storageUsage(Request $request)
    {
        $partnerId = auth('partner')->user()->partner_id;
        
        $query = Tenant::with(['plan', 'storageUsage'])
            ->where('partner_id', $partnerId)
            ->where('active', true);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('fantasy_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $tenants = $query->orderBy('name')->paginate(25);
        
        return view('partner.storage-usage', compact('tenants'));
    }

    public function inviteClient()
    {
        return view('partner.invite-client');
    }

    public function generateInviteLink(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'message' => 'nullable|string|max:1000',
            'action' => 'required|in:email,whatsapp',
        ]);

        $user = auth('partner')->user();
        $partner = Partner::findOrFail($user->partner_id);
        
        // Gerar token único para o convite
        $token = Str::random(60);
        
        // Armazenar token no cache com informações do parceiro (válido por 30 dias)
        Cache::put("partner_invite_{$token}", [
            'partner_id' => $partner->id,
            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
        ], now()->addDays(30));
        
        // Gerar link de cadastro
        $registerUrl = route('tenant.register', ['token' => $token]);
        
        if ($request->action === 'email') {
            // Enviar por e-mail
            $active = SmtpConfig::where('is_active', true)->first();
            if ($active) {
                $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
                $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
                $username = (string) ($active->username ?? env('MAIL_USERNAME'));
                $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
                $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
                $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
                $fromName = (string) ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name')));
                
                $message = $request->message ?: "Olá {$request->client_name}, você foi convidado(a) pela {$partner->name} para se cadastrar no QFiscal. Clique no link abaixo para começar seu cadastro.";
                
                $html = view('emails.partners.client_invite', compact('partner', 'registerUrl', 'message', 'request'))->render();
                
                try {
                    $mailer = new PHPMailer(true);
                    EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
                    $mailer->addAddress($request->client_email, $request->client_name);
                    $mailer->isHTML(true);
                    $mailer->Subject = "Convite de Cadastro - {$partner->name}";
                    $mailer->Body = $html;
                    $mailer->AltBody = strip_tags($html);
                    $mailer->send();
                    
                    return redirect()->route('partner.invite-client')->with('success', "Convite enviado por e-mail para {$request->client_email} com sucesso!");
                } catch (PHPMailerException $e) {
                    return redirect()->route('partner.invite-client')->with('error', 'Erro ao enviar e-mail. Verifique as configurações SMTP.');
                }
            } else {
                return redirect()->route('partner.invite-client')->with('error', 'Configuração SMTP não encontrada. Entre em contato com o suporte.');
            }
        } else {
            // Gerar link para WhatsApp
            $whatsappMessage = $request->message ?: "Olá {$request->client_name}! Você foi convidado(a) pela {$partner->name} para se cadastrar no QFiscal. Clique no link para começar: {$registerUrl}";
            $whatsappText = urlencode($whatsappMessage);
            
            // Se o parceiro tem telefone, usar o número dele, senão usar o link genérico
            $phone = $partner->contact_phone ? preg_replace('/[^0-9]/', '', $partner->contact_phone) : null;
            $whatsappUrl = $phone ? "https://wa.me/55{$phone}?text={$whatsappText}" : "https://wa.me/?text={$whatsappText}";
            
            return redirect()->route('partner.invite-client')
                ->with('success', "Link gerado com sucesso!")
                ->with('invite_link', $registerUrl)
                ->with('whatsapp_url', $whatsappUrl);
        }
    }
}


