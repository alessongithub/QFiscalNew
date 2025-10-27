<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use App\Models\SmtpConfig;
use App\Http\Controllers\Admin\EmailTestController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class PartnerPublicController extends Controller
{
    public function showForm()
    {
        return view('public.partners.apply');
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'required|string|max:100|alpha_dash|unique:partners,slug',
            'cnpj' => 'required|string|min:14|max:20',
            'crc' => 'nullable|string|max:50',
            'contact_name' => 'required|string|max:150',
            'contact_email' => 'required|email|max:190',
            'contact_phone' => 'required|string|min:10|max:50',
        ]);
        // Normalizar CNPJ e WhatsApp para apenas dígitos
        $cnpjDigits = preg_replace('/[^0-9]/', '', $data['cnpj']);
        $phoneDigits = preg_replace('/[^0-9]/', '', $data['contact_phone']);
        if (strlen($cnpjDigits) !== 14) {
            return back()->withErrors(['cnpj' => 'CNPJ inválido.'])->withInput();
        }
        if (strlen($phoneDigits) < 10) {
            return back()->withErrors(['contact_phone' => 'WhatsApp inválido.'])->withInput();
        }

        $partner = Partner::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'cnpj' => $cnpjDigits,
            'crc' => $data['crc'] ?? null,
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $phoneDigits,
            'commission_percent' => 0.3000,
            'active' => false,
            'applied_at' => now(),
        ]);

        // Enviar e-mails (confirmação para o parceiro e notificação interna)
        $active = SmtpConfig::where('is_active', true)->first();
        $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
        $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
        $username = (string) ($active->username ?? env('MAIL_USERNAME'));
        $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
        $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
        $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
        $fromName = (string) ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name')));

        $confirmHtml = view('emails.partners.confirmation', ['partner' => $partner])->render();
        $adminHtml = view('emails.partners.admin_notification', ['partner' => $partner])->render();

        // Confirmação para o parceiro
        try {
            $mailer = new PHPMailer(true);
            EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
            $mailer->addAddress($partner->contact_email, $partner->contact_name ?: $partner->name);
            $mailer->isHTML(true);
            $mailer->Subject = 'Recebemos sua inscrição - Programa de Parceria QFiscal';
            $mailer->Body = $confirmHtml;
            $mailer->AltBody = strip_tags($confirmHtml);
            $mailer->send();
        } catch (PHPMailerException $e) {
            // silencioso: não bloquear fluxo
        }

        // Notificação interna
        try {
            $adminTo = env('PARTNER_ADMIN_EMAIL', $fromAddress ?: $username);
            if (!empty($adminTo)) {
                $mailer2 = new PHPMailer(true);
                EmailTestController::configureMailer($mailer2, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
                $mailer2->addAddress($adminTo, 'Equipe QFiscal');
                $mailer2->isHTML(true);
                $mailer2->Subject = 'Nova inscrição de parceiro - ' . $partner->name;
                $mailer2->Body = $adminHtml;
                $mailer2->AltBody = strip_tags($adminHtml);
                $mailer2->send();
            }
        } catch (PHPMailerException $e) {
            // silencioso
        }

        return redirect()->route('partner.apply')->with('success','Recebemos sua inscrição. Entraremos em contato para validação.');
    }
}


